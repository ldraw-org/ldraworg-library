<?php

namespace App\Services\Part\Submit;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Services\Check\CheckMessage;
use App\Services\Check\CheckMessageCollection;
use App\Services\Check\PartChecker;
use App\Services\LDraw\LDrawFile;
use App\Services\Parser\ParsedPartCollection;

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
                        'error' => PartError::DuplicateFile,
                        'value' => $part->type() === PartType::Primitive ? 'Parts' : 'Primitive',
                    ]));
                }
            }
        }

        elseif ($file->mimetype === 'image/png') {

            $parts = Part::query()
                ->whereLike('filename', "%{$file->filename}")
                ->get();

            $unofficialExists = $parts->unofficial()->isNotEmpty();
            $officialExists   = $parts->official()->isNotEmpty();
        }

        else {
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
}
