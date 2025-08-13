<?php

namespace App\Livewire\Dashboard;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Livewire\Attributes\Layout;
use Livewire\Component;

abstract class BasicResourceManagePage extends Component implements HasSchemas, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public string $title = '';
    protected string $menu = 'user';

    abstract public function table(Table $table): Table;

    #[Layout('components.layout.base')]
    public function render()
    {
        return view('livewire.dashboard.basic-resource-manage-page', ['menu' => $this->menu]);
    }
}
