<?php

namespace App\Livewire\Part;

use App\Livewire\Tables\PartTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table as Table;
use Livewire\Component;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

class Index extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public bool $unofficial = false;

    #[Url()]
    public ?array $tableFilters = null;
    #[Url]
    public ?string $tableSortColumn = null;
    #[Url]
    public ?string $tableSortDirection = null;
    #[Url]
    public $tableRecordsPerPage = null;

    public function mount()
    {
        $this->unofficial = Route::currentRouteName() == 'official.index' ? false : true;
    }

    public function table(Table $table): Table
    {
        return PartTable::table($table, !$this->unofficial);
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.part.part-list');
    }
}
