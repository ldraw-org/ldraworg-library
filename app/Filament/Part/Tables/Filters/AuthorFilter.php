<?php

namespace App\Filament\Part\Tables\Filters;

use App\Models\User;
use Filament\Tables\Filters\SelectFilter;

class AuthorFilter
{
    public static function make(?string $name = null): SelectFilter
    {
        return SelectFilter::make($name)
            ->relationship(name: 'user', titleAttribute: 'name')
            ->getOptionLabelFromRecordUsing(fn (User $u) => $u->authorString)
            ->native(false)
            ->searchable()
            ->preload()
            ->label('Author');
    }
}
