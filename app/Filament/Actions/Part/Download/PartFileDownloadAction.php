<?php

namespace App\Filament\Actions\Part\Download;

use App\Models\Part\Part;
use Filament\Actions\Action;

class PartFileDownloadAction
{ 
    public static function make(?string $name = null): Action
    {
        return Action::make($name)
            ->url(fn (Part $part) => route($part->isUnofficial() ? 'unofficial.download' : 'official.download', $part->filename))
            ->button()
            ->outlined()
            ->color('info');
    }
}