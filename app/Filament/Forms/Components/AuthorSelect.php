<?php

namespace App\Filament\Forms\Components;

use App\Filament\Traits\HasAuthorComponentSetup;
use Filament\Forms\Components\Select;

class AuthorSelect
{
    use HasAuthorComponentSetup;
    public static function make(): Select
    {
        return static::configureAuthorComponent(
            Select::make('user_id')
        )
            ->selectablePlaceholder(false)
            ->rules(['exists:App\Models\User,id']);
    }
}
