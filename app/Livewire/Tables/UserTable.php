<?php

namespace App\Livewire\Tables;

use App\Enums\Permission;
use App\Models\Mybb\MybbUser;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Spatie\Permission\Models\Role;

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
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('User Name')
                    ->sortable()
                    ->searchable(),
                ViewColumn::make('roles')
                    ->view('tables.columns.user-roles'),
                TextColumn::make('email')
                    ->searchable()
                    ->visible(Auth::user()?->can(Permission::UserViewEmail) ?? false),
                TextColumn::make('license')
                    ->sortable(),
                TextColumn::make('part_events_max_created_at')
                    ->label('Last Library Action')
                    ->since()
                    ->sortable(),
                TextColumn::make('forum_user.lastactive')
                    ->label('Last Active on Forum')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->multiple()
            ]);
    }
}