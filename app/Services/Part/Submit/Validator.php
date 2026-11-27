<?php

namespace App\Services\Part\Submit;

use App\Enums\LDrawRegex;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Services\Check\CheckMessage;
use App\Services\Check\CheckMessageCollection;
use App\Services\Check\Enums\PartError;
use App\Services\Check\PartChecker;
use App\Services\Check\PartChecks\LibraryApprovedName;
use App\Services\LDraw\LDrawFile;
use App\Services\Parser\ParsedPartCollection;
use Illuminate\Support\Str;

class Validator
{
    public function validate(LDrawFile $file, bool $replace, bool $officialFix): CheckMessageCollection
    {
        $unofficialExists = false;
        $officialExists = false;
        $checkmessages = new CheckMessageCollection();

        if ($file->mimetype === 'text/plain') {
            $part = new ParsedPartCollection($file->contents);

            $partname = $part->name() ?? '';
            $parts = Part::query()->byName($partname)->get();

            $unofficialExists = $parts->unofficial()->isNotEmpty();
            $officialExists   = $parts->official()->isNotEmpty();

            $checkmessages = app(PartChecker::class)->run($part, $file->filename);

            if ($part->type() === PartType::Primitive || $part->type()?->inPartsFolder()) {
                $folder = $part->type() === PartType::Primitive ? 'parts/' : 'p/';

                if ($parts->where('filename', "{$folder}{$partname}")->isNotEmpty()) {
                    $checkmessages->push(CheckMessage::fromArray([
                        'check' => PartError::DuplicateFile,
                        'value' => $part->type() === PartType::Primitive ? 'Parts' : 'Primitive',
                    ]));
                }
            }
        } elseif ($file->mimetype === 'image/png') {
            // Texmaps are saved under their lowercased filename
            if (! preg_match(LibraryApprovedName::PATTERN, Str::lower($file->filename))) {
                $checkmessages->push(CheckMessage::fromPartError(PartError::PartNameInvalid));
            }

            $parts = Part::query()->byName($file->filename)->get();

            $unofficialExists = $parts->unofficial()->isNotEmpty();
            $officialExists   = $parts->official()->isNotEmpty();
        } else {
            $checkmessages->push(
                CheckMessage::fromPartError(PartError::InvalidFileFormat)
            );
        }

        if ($unofficialExists && !$replace) {
            $checkmessages->push(CheckMessage::fromPartError(PartError::ReplaceNotSelected));
        }

        if ($officialExists && !$unofficialExists && !$officialFix) {
            $checkmessages->push(CheckMessage::fromPartError(PartError::FixNotSelected));
        }

        return $checkmessages;
    }

    /**
     * The name a file will be saved under, lowercased: a part's Name: line or a texmap's filename.
     */
    public function submittedName(LDrawFile $file): ?string
    {
        $name = match ($file->mimetype) {
            'text/plain' => LDrawRegex::Name->findIn($file->contents, $matches) ? $matches['name'] : null,
            'image/png' => $file->filename,
            default => null,
        };

        return is_null($name) ? null : Str::lower(str_replace('\\', '/', $name));
    }
}
