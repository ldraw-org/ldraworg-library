<?php

namespace App\Livewire\Tables;

use App\Enums\Permission;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

/**
 * @property Table $table
 */
class UserTable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->where('is_ptadmin', false)->where('is_synthetic', false))
            ->defaultSort('realname', 'asc')
            ->heading('User List')
            ->columns([
                TextColumn::make('realname')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable()
                    ->visible(Auth::user()?->can(Permission::UserViewEmail)),
                TextColumn::make('license.name')
                    ->sortable(),
                TextColumn::make('last-action')
                    ->state(fn (User $user) => $user->part_events->isEmpty() ? 'None' : (new Carbon($user->part_events()->latest()->first()->created_at)->longRelativeToNowDiffForHumans())),
            ]);
    }
}