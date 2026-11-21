<?php

namespace App\Livewire\Tables;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Base for Livewire components whose only content is a Filament table.
 *
 * Embedded tables use the default render(); full-page tables override
 * render() to set their layout and view.
 */
abstract class BasicTable extends Component implements HasSchemas, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    abstract public function table(Table $table): Table;

    public function render(): View
    {
        return view('livewire.tables.basic-table');
    }
}
