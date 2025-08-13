<?php

namespace App\Filament\Forms\Components;

use App\Models\User;
use Filament\Forms\Components\Select;

class AuthorSelect
{
    public static function make(): Select
    {
        return Select::make('user_id')
            ->options(User::all()->sortBy('authorString')->pluck('authorString', 'id'))
            ->selectablePlaceholder(false)
            ->searchable()
            ->label('Author')
            ->rules(['exists:App\Models\User,id']);
    }
}
