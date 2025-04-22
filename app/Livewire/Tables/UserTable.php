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
            ->query(User::query()->withMax('part_events','created_at')->where('is_ptadmin', false)->where('is_synthetic', false))
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
                    ->visible(Auth::user()?->can(Permission::UserViewEmail) ?? false),
                TextColumn::make('license')
                    ->sortable(),
                TextColumn::make('part_events_max_created_at')
                    ->label('Last Action')
                    ->since()
                    ->sortable(),
            ]);
    }
}