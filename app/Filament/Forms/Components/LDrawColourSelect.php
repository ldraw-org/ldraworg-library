<?php

namespace App\Filament\Forms\Components;

use App\Services\LDraw\Managers\LDConfigManager;
use Filament\Forms\Components\Select;

class LDrawColourSelect
{
    public static function make(string $name): Select
    {
        return Select::make($name)
            ->searchable()
            ->preload()
            ->placeholder('Select a color')
            ->allowHtml()
            ->options(app(LDConfigManager::class)->ldrawColourOptions());
    }
}
