<?php

namespace App\Filament\Actions\Part\Download;

use App\Models\Part\Part;
use Filament\Actions\Action;

class PartFileDownloadAction
{
    public static function make(?string $name = null, ?Part $part = null): Action
    {
        return Action::make($name ?? 'download')
            ->url(
                is_null($part)
                    ? fn (Part $p) => route('part.download', ['library' => $p->libFolder(), 'filename' => $p->filename])
                    : route('part.download', ['library' => $part->libFolder(), 'filename' => $part->filename])
            );
    }
}
