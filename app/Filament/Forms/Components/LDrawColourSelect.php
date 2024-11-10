<?php

namespace App\Filament\Forms\Components;

use App\Models\LdrawColour;
use Filament\Forms\Components\Select;

class LDrawColourSelect
{

    public static function make(?string $name): Select
    {
        return Select::make($name)
            ->searchable()
            ->preload()
            ->native(false)
            ->extraAlpineAttributes(['x-on:click' => 'select.config.fuseOptions = {location: 87, threshold: 0.2}'])
            ->allowHtml()
            ->options(self::getOptions());
    }

    protected static function getOptions(): array
    {
        return LdrawColour::orderBy('code')
            ->get()
            ->mapWithKeys(fn (LdrawColour $color) =>
                [$color->code => "<span class=\"{$color->labelTextColor()} rounded px-2 py-1\" style=\"background-color: {$color->value}\">{$color->code} - {$color->name}</span>"]
            )
            ->all();
    }
}