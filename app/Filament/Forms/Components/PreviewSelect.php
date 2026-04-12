<?php

namespace App\Filament\Forms\Components;

use App\Enums\PreviewRotation;
use Filament\Forms\Components\Select;
use Illuminate\Validation\Rule;

class PreviewSelect
{
    public static function make(string $name = 'preview_rotation'): Select
    {
        return Select::make($name)
            ->extraAttributes(['class' => 'font-mono'])
            ->options(PreviewRotation::class)
            ->required()
            ->rules([
                Rule::enum(PreviewRotation::class),
            ]);
    }
}
