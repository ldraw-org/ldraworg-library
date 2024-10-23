<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleManagePage extends BasicResourceManagePage
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $title = "Manage Roles";

    public function table(Table $table): Table
    {
        return $table
            ->query(Role::query())
            ->defaultSort('name')
            ->heading('Role Management')
            ->paginated(false)
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->sortable()
            ])
            ->actions([
                EditAction::make()
                    ->form($this->formSchema()),
                DeleteAction::make()
                    ->hidden(fn (Role $r) => $r->users->count() == 0)
            ])
            ->headerActions([
                CreateAction::make()
                    ->form($this->formSchema())
            ]);
    }

    protected function formSchema(): array
    {
        return [
            TextInput::make('name')
                ->string()
                ->required(),
            CheckboxList::make('permissions')
                ->relationship(titleAttribute: 'name'),
        ];
    }
}
