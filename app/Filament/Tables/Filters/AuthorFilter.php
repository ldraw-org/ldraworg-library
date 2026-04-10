<?php

namespace App\Filament\Tables\Filters;

use App\Filament\Traits\HasAuthorComponentSetup;
use App\Models\User;
use Filament\Tables\Filters\SelectFilter;

class AuthorFilter
{
    use HasAuthorComponentSetup;
    public static function make(?string $name = null): SelectFilter
    {
        return static::configureAuthorComponent(
            SelectFilter::make($name)
        )
            ->preload();
    }
}
