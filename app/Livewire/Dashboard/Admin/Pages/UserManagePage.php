<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use App\Enums\License;
use App\Enums\Permission;
use App\Livewire\Dashboard\BasicResourceManagePage;
use App\Models\Mybb\MybbUser;
use App\Models\User;
use App\Settings\LibrarySettings;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Set;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table as Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserManagePage extends BasicResourceManagePage
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $title = "Manage Users";
    protected string $menu = 'admin';

    public function mount(): void
    {
        $this->authorize('add', User::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query())
            ->defaultSort('realname', 'asc')
            ->heading('User Management')
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
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
                TextColumn::make('roles.name')
                    ->wrap(),
                TextColumn::make('parts_count')
                    ->counts('parts')
                    ->sortable()
                    ->url(fn (User $u) => route('parts.list', ['tableSearch' => "\"Author: $u->realname\""]))
            ])
            ->filters([
                SelectFilter::make('license')
                    ->options(License::options())
                    ->preload()
                    ->multiple()
                    ->native(false),
                SelectFilter::make('roles')
                    ->relationship('roles', titleAttribute: 'name')
                    ->preload()
                    ->multiple()
                    ->native(false),
            ])
            ->headerActions([
                CreateAction::make()
                    ->form($this->formSchema())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['password'] = bcrypt(Str::random(40));
                        return $data;
                    })
                    ->visible(fn () => Auth::user()?->can('create', User::class))
            ])
            ->actions([
                EditAction::make()
                    ->form($this->formSchema())
                    ->visible(fn (User $u) => Auth::user()?->can('update', $u))
            ]);
    }

    protected function formSchema(): array
    {
        return [
            Select::make('forum_user_id')
                ->label('Forum User Name')
                ->options(
                    MybbUser::whereNotIn('usergroup', [5,7])
                        ->whereNotIn('uid', User::whereNotNull('forum_user_id')->pluck('forum_user_id')->all())
                        ->orderBy('username')
                        ->pluck('username', 'uid')
                )
                ->required()
                ->live()
                ->searchable()
                ->afterStateUpdated(function (Set $set, int $state) {
                    $user = MybbUser::find($state);
                    if (!is_null($user)) {
                        $set('realname', $user->username);
                        $set('name', $user->loginname);
                        $set('email', $user->email);
                    }
                })
                ->hiddenOn('edit'),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('realname')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255),
            Select::make('license')
                ->options(License::options())
                ->default(app(LibrarySettings::class)->default_part_license)
                ->native(false)
                ->required(),
            Select::make('roles')
                ->relationship('roles', titleAttribute: 'name')
                ->multiple()
                ->native(false)
                ->preload()
                ->required(),
            Fieldset::make('Special Account Types')
                ->schema([
                    Checkbox::make('is_legacy')
                        ->label('Legacy User'),
                    Checkbox::make('is_synthetic')
                        ->label('Synthetic User'),
                    Checkbox::make('is_ptadmin')
                        ->label('Parts Tracker Automated User'),
                ])
                ->columns(3),
        ];
    }
}
