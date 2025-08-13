<?php

namespace App\Filament\Tables\Filters;

use App\Models\User;
use Filament\Tables\Filters\SelectFilter;

class AuthorFilter
{
    public static function make(?string $name = null): SelectFilter
    {
        return SelectFilter::make($name)
            ->options(User::all()->sortBy('authorString')->pluck('authorString', 'id'))
            ->selectablePlaceholder(false)
            ->searchable()
            ->label('Author');
    }
}
