<?php

namespace App\Services\Part;

use App\Collections\PartCollection;
use App\Enums\LdrawFileType;
use App\Enums\PartType;
use App\Events\PartSubmitted;
use App\Models\Part\Part;
use App\Models\User;
use App\Services\LDraw\LDrawFile;
use App\Services\Parser\ParsedPartCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PartSubmissionService
{
    public function __construct()
    {
    }

    public function submitFiles(Collection $files, User $user, ?string $comments = null): PartCollection
    {
        $submittedFiles = new PartCollection();
        foreach($files as $file) {
            $part = match ($file->filetype) {
                LdrawFileType::Image => $this->makePartFromImage($file, $user, $this->guessPartType($file->filename, $files)),
                LdrawFileType::TextFile => $this->makePartFromText($file),
            };
            PartSubmitted::dispatch($part, $user, $comments);
            $submittedFiles->push($part);
        }
        return $submittedFiles;
    } 

    public function guessPartType(string $filename, Collection $files): PartType
    {
        // Check if part exists and return that type
        $p = Part::firstWhere('filename', 'LIKE', "%/textures%/{$filename}");
        if (!is_null($p)) {
            return $p->type;
        }
        // See if it is used by the group of submitted files
        $p = $files->first(
            fn (LDrawFile $f, int $key) =>
                $f->filetype == LdrawFileType::TextFile && Str::containsAll($f->contents, ['!TEXMAP', $filename])
        );
        if (!is_null($p)) {
            $p = (new ParsedPartCollection($p->content));
            $type = $p->type();
            return is_null($type) ? PartType::PartTexmap : $type;
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
        ];
        $upart = $this->makePart($attributes);
        $upart->setBody(base64_encode($file->contents));
        return $upart;
    }

    protected function makePartFromText(LDrawFile $file): Part
    {
        $part = new ParsedPartCollection($file->contents);

        $user = $part->authorUser();
        $filename = $part->type()->folder() . '/' . basename(str_replace('\\', '/', $part->name()));
        $preview = $part->preview() == '16 0 0 0 1 0 0 0 1 0 0 0 1' ? null : $part->preview();
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
            'preview' => $preview,
            'help' => $part->help(),
            'header' => ''
        ];
        $upart = $this->makePart($values);
        $preview_vals = $upart->previewValues();
        if ($preview_vals['color'] != 16 ||
            $preview_vals['x'] != 0 ||
            $preview_vals['y'] != 0 ||
            $preview_vals['z'] != 0
        ) {
            $upart->preview = '16 0 0 0 ' . $preview_vals['rotation'];
            $upart->preview = $upart->preview == '16 0 0 0 1 0 0 0 1 0 0 0 1' ? null : $upart->preview;
            $upart->save();
        }
        $upart->setKeywords($part->keywords() ?? []);
        $upart->setHistory($part->history() ?? []);
        $upart->setBody($part->bodyText());
        $upart->refresh();
        return $upart;
    }

    protected function makePart(array $values): Part
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