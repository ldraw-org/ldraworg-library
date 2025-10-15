<?php

namespace App\Filament\Actions\Part\Download;

use App\Models\Part\Part;
use Filament\Actions\Action;
use Illuminate\Support\Str;

class PartZipFileDownloadAction
{
    public static function make(?string $name = null, ?Part $part = null): Action
    {
        return Action::make($name ?? 'download-zip')
            ->url(
                is_null($part)
                    ? fn (Part $p) => route('part.download', ['library' => $p->libFolder(), 'filename' => Str::replaceLast('.dat', '.zip', $p->filename)])
                    : route('part.download', ['library' => $part->libFolder(), 'filename' => Str::replaceLast('.dat', '.zip', $part->filename)])
            );
    }
}
