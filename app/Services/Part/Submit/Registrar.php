<?php

namespace App\Services\Part\Submit;

use App\Enums\PartType;
use App\Enums\PreviewRotation;
use App\Events\PartSubmitted;
use App\Jobs\UpdateZip;
use App\Models\Part\Part;
use App\Models\User;
use App\Services\LDraw\LDrawFile;
use App\Services\Parser\ParsedPartCollection;
use App\Services\Part\BasePartSync;
use App\Services\Part\Finalizer;
use App\Services\Part\ImageGenerator;
use App\Services\Part\SyncSubparts;
use App\Services\Part\Validator;
use App\Services\Part\Writer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class Registrar
{
    public function __construct(
        protected SyncSubparts   $subpartSync,
        protected ImageGenerator $imageGenerator,
        protected Validator      $validator,
        protected BasePartSync   $basePartSync,
        protected Finalizer      $finalizer,
        protected Writer $writer,
    ) {}

    public function submit(LDrawFile|SupportCollection|array $files, User $user, ?string $comments = null): Collection
    {
        if (!$files instanceof SupportCollection) {
            $files = is_array($files) ? $files : [$files];
            $files = collect($files);
        }
        // Parse each part into the tracker
        $parts = new Collection($files->map(function (LDrawFile $file, int $key) use ($files, $user) {
            if ($file->mimetype == 'image/png') {
                return $this->makePartFromImage($file, $user, $this->guessPartType($file->filename, $files));
            } elseif ($file->mimetype == 'text/plain') {
                return $this->makePartFromText($file);
            }
            return null;
        })->all());
        $this->finalizer->handle($parts);
        $parts->each(function (Part $part) use ($user, $comments) {
            $user->notification_parts()->syncWithoutDetaching([$part->id]);
            UpdateZip::dispatch($part);
            PartSubmitted::dispatch($part, $user);
        });

        return $parts;
    }

    public function guessPartType(string $filename, SupportCollection $files): PartType
    {
        // Check if part exists and return that type
        $p = Part::firstWhere('filename', 'LIKE', "%/textures%/{$filename}");
        if (!is_null($p)) {
            return $p->type;
        }
        // See if it is used by the group of submitted files
        $p = $files->first(
            fn (LDrawFile $f, int $key) =>
                $f->mimetype == 'text/plain' && Str::containsAll($f->contents, ['!TEXMAP', $filename])
        );
        if (!is_null($p)) {
            $p = (new ParsedPartCollection($p->content));
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
            'header' => '',
            'missing_parts' => [],
        ];
        return $this->writer->createOrUpdate(
            $attributes,
            base64_encode($file->contents),
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
            'category' => $part->category(),
            'cmdline' => $part->cmdline(),
            'preview' => $part->previewRotation() ?? PreviewRotation::Default,
            'help' => $part->help(),
            'header' => '',
            'missing_parts' => [],
        ];
        return $this->writer->createOrUpdate(
            $values,
            $part->bodyText(),
            $part->keywords() ?? [],
            $part->history() ?? []
        );
    }


    public function makePart(array $values): Part
    {
        $upart = Part::unofficial()->firstWhere('filename', $values['filename']);
        $opart = Part::official()->firstWhere('filename', $values['filename']);
        if (!is_null($upart)) {
            store_backup(str_replace('/', '-', $upart->filename), $upart->get());
            $upart->votes()->delete();
            $upart->fill($values);
            $upart->save();
        } elseif (!is_null($opart)) {
            $upart = Part::create($values);
            $opart->unofficial_part()->associate($upart);
            $opart->save();
        } else {
            $upart = Part::create($values);
        }
        return $upart;
    }



}
