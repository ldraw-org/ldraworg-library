<?php

namespace App\Filament\Traits;

use App\Services\User\UserList;

trait HasAuthorComponentSetup
{
    protected static function configureAuthorComponent($component)
    {
        return $component
            ->options(fn() => app(UserList::class)->userOptions())
            ->searchable()
            ->label('Author');
    }
}
