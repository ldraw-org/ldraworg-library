<?php

namespace App\Services\Part\Submit;

use App\Enums\PartType;
use App\Enums\PreviewRotation;
use App\Events\PartSubmitted;
use App\Jobs\FinalizeParts;
use App\Jobs\UpdateZip;
use App\Models\Part\Part;
use App\Models\User;
use App\Services\LDraw\LDrawFile;
use App\Services\Parser\ParsedPartCollection;
use App\Services\Part\Finalizer;
use App\Services\Part\TexmapOptimizer;
use App\Services\Part\Writer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class Registrar
{
    public function __construct(
        protected Finalizer $finalizer,
        protected Writer $writer,
        protected TexmapOptimizer $texmapOptimizer,
    ) {
    }

    public function submit(SupportCollection $files, User $user, ?string $comments = null): Collection
    {
        // Save every part with its submit event, or none of them. Only these can't be rebuilt from the saved part later.
        $parts = DB::transaction(function () use ($files, $user, $comments): Collection {
            $parts = Part::make()->newCollection();

            foreach ($files as $file) {
                $part = match($file->mimetype) {
                    'image/png'  => $this->makePartFromImage($file, $user, $this->guessPartType($file->filename, $files)),
                    'text/plain' => $this->makePartFromText($file),
                    default => null,
                };

                if ($part) {
                    $parts->push($part);
                }
            }

            $parts->each(function (Part $part) use ($user, $comments): void {
                $user->notification_parts()->syncWithoutDetaching([$part->id]);
                PartSubmitted::dispatch($part, $user, $comments);
            });

            return $parts;
        });

        // Everything from here is derived from the saved parts, so a failure is reported and retried rather than failing the submit
        try {
            $this->finalizer->handle($parts);
        } catch (Throwable $e) {
            report($e);
            FinalizeParts::dispatch($parts);
        }

        $parts->each(fn (Part $part) => UpdateZip::dispatch($part));

        return $parts;
    }

    public function guessPartType(string $filename, SupportCollection $files): PartType
    {
        // Check if part exists and return that type
        $p = Part::query()->byName($filename)->first();
        if (!is_null($p)) {
            return $p->type;
        }
        // See if it is used by the group of submitted files
        $p = $files->first(
            fn (LDrawFile $f, int $key) =>
                $f->mimetype == 'text/plain' && Str::containsAll($f->contents, ['!TEXMAP', $filename])
        );
        if (!is_null($p)) {
            $p = (new ParsedPartCollection($p->contents));
            $type = $p->type();
            return is_null($type) ? PartType::PartTexmap : PartType::tryfrom($type->value . '_Texmap');
        }

        return PartType::PartTexmap;
    }

    protected function makePartFromImage(LDrawFile $file, User $user, PartType $type): Part
    {
        $filename = $type->folder() . '/' . basename(str_replace('\\', '/', $file->filename));
        $attributes = [
            'user_id' => $user->id,
            'license' => $user->license,
            'filename' => $filename,
            'description' => "{$type->description()} {$filename}",
            'type' => $type,
            'missing_parts' => [],
        ];
        return $this->writer->createOrUpdate(
            $attributes,
            base64_encode($this->texmapOptimizer->optimize($file->contents)),
        );
    }

    protected function makePartFromText(LDrawFile $file): Part
    {
        $part = new ParsedPartCollection($file->contents);

        $user = $part->authorUser();
        $filename = $part->type()->folder() . '/' . basename(str_replace('\\', '/', $part->name()));
        $values = [
            'description' => $part->description(),
            'filename' => $filename,
            'user_id' => $user->id,
            'type' => $part->type(),
            'type_qualifier' => $part->type_qualifier(),
            'license' => $user->license,
            'bfc' => $part->headerBfc(),
            'category' => $part->type()?->inPartsFolder() ? $part->category() : null,
            'cmdline' => $part->cmdline(),
            'preview' => $part->previewRotation() ?? PreviewRotation::Default,
            'help' => $part->help(),
            'missing_parts' => [],
        ];
        return $this->writer->createOrUpdate(
            $values,
            $part->bodyText(),
            $part->keywords() ?? [],
            $part->history() ?? []
        );
    }



}
