<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\CreateAction;
use App\Livewire\Dashboard\BasicResourceManagePage;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleManagePage extends BasicResourceManagePage implements HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public string $title = "Manage Roles";
    protected string $menu = 'admin';

    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);
    }

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
            ->recordActions([
                EditAction::make()
                    ->schema($this->formSchema()),
                DeleteAction::make()
                    ->hidden(fn (Role $r) => $r->users->isEmpty())
            ])
            ->headerActions([
                CreateAction::make()
                    ->schema($this->formSchema())
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
