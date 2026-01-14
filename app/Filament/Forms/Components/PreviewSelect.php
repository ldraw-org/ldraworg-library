<?php

namespace App\Filament\Forms\Components;

use App\Rules\PreviewIsValid;
use Filament\Forms\Components\Select;

class PreviewSelect
{
    public static function make(): Select
    {
        return Select::make('preview_rotation')
            ->extraAttributes(['class' => 'font-mono'])
            ->options([
                '1 0 0 0 1 0 0 0 1' => 'Default (No Rotation)',
                '1 0 0 0 0 1 0 -1 0' => 'Rotated -90 around X',
                '1 0 0 0 -1 0 0 0 -1' => 'Rotated 180 around X',
                '1 0 0 0 0 -1 0 1 0' => 'Rotated 90 around X',
                '0 0 1 0 1 0 -1 0 0' => 'Rotated -90 around Y',
                '-1 0 0 0 1 0 0 0 -1' => 'Rotated 180 around Y',
                '0 0 -1 0 1 0 1 0 0' => 'Rotated 90 around Y',
                '0 -1 0 1 0 0 0 0 1' => 'Rotated -90 around Z',
                '-1 0 0 0 -1 0 0 0 1' => 'Rotated 180 around Z',
                '0 1 0 -1 0 0 0 0 1' => 'Rotated 90 around Z',
            ])
            ->required()
            ->rules([new PreviewIsValid()]);
    }
}
