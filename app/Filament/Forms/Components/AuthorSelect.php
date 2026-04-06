<?php

namespace App\Filament\Forms\Components;

use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;

class AuthorSelect
{
    public static function make(): Select
    {
        return Select::make('user_id')
            ->relationship('user', 'author_string')
            ->searchable()
            ->selectablePlaceholder(false)
            ->label('Author')
            ->rules(['exists:App\Models\User,id']);
    }
}
