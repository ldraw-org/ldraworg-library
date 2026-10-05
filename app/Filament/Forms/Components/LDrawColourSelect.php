<?php

namespace App\Filament\Forms\Components;

use App\Models\LdrawColour;
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
            // Tune fuzzy search behavior for colour-code matching
            ->extraAlpineAttributes(['x-on:click' => 'select.config.fuseOptions = {location: 87, threshold: 0.2}'])
            ->allowHtml()
            ->options(app(LDConfigManager::class)->ldrawColourOptions());
    }
}
