<?php

namespace App\Filament\Forms\Components;

use App\Models\User;
use Filament\Forms\Components\Select;

class AuthorSelect
{
    public static function make(): Select
    {
        return Select::make('user_id')
            ->relationship(name: 'user', titleAttribute: 'name')
            ->getOptionLabelFromRecordUsing(fn (User $u) => $u->authorString)
            ->selectablePlaceholder(false)
            ->searchable()
            ->preload()
            ->label('Author')
            ->exists(table: User::class, column: 'id');
    }
}
