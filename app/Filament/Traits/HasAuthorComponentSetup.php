<?php

namespace App\Filament\Traits;

trait HasAuthorComponentSetup
{
    protected static function configureAuthorComponent($component)
    {
        return $component
            ->relationship('user', 'author_string')
            ->searchable()
            ->label('Author');
    }
}
