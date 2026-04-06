<?php

namespace App\Filament\Tables\Filters;

use App\Models\User;
use Filament\Tables\Filters\SelectFilter;

class AuthorFilter
{
    public static function make(?string $name = null): SelectFilter
    {
        return SelectFilter::make($name)
            ->relationship('user', 'author_string')
            ->preload()
            ->searchable()
            ->label('Author');
    }
}
