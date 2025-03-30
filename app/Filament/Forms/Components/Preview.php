<?php

namespace App\Filament\Forms\Components;

use App\Models\LdrawColour;
use App\Rules\PreviewIsValid;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class Preview
{
    public static function component(): Fieldset
    {
        return Fieldset::make('preview')
            ->schema([
                LDrawColourSelect::make('preview_color')
                    ->requiredWith('preview_x,preview_y,preview_z,preview_rotation')
                    ->exists(table: LdrawColour::class, column: 'code')
                    ->columnSpan(3),
                TextInput::make('preview_x')
                    ->extraAttributes(['class' => 'font-mono'])
                    ->numeric()
                    ->rules([new PreviewIsValid()])
                    ->requiredWith('preview_color,preview_y,preview_z,preview_rotation'),
                TextInput::make('preview_y')
                    ->extraAttributes(['class' => 'font-mono'])
                    ->numeric()
                    ->rules([new PreviewIsValid()])
                    ->requiredWith('preview_color,preview_x,preview_z,preview_rotation'),
                TextInput::make('preview_z')
                    ->extraAttributes(['class' => 'font-mono'])
                    ->numeric()
                    ->rules([new PreviewIsValid()])
                    ->requiredWith('preview_color,preview_x,preview_y,preview_rotation'),
                Select::make('preview_rotation')
                    ->extraAttributes(['class' => 'font-mono'])
                    ->options([
                        '1 0 0 0 1 0 0 0 1' => 'Standard',
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
                    ->requiredWith('preview_color,preview_x,preview_y,preview_z')
                    ->rules([new PreviewIsValid()])
                    ->columnSpan(3),
            ])
            ->columns(3);
    }
}
