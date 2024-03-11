<?php

namespace App\Livewire\Part;

use App\Models\Part;
use App\Tables\Part\PartTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table as Table;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Weekly extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;


    public function table(Table $table): Table
    {

        return $table
            ->query(Part::unofficial()->whereRelation('type', 'folder', 'parts/')->whereNull('official_part_id'))
            ->defaultSort('created_at', 'asc')
            ->emptyState(view('tables.empty', ['none' => 'None']))
            ->columns(PartTable::columns())
            ->groups([
                Group::make('week')
                    ->date(),
            ])
            ->actions(PartTable::actions()) 
            ->defaultGroup('week')   
            ->recordUrl(
                fn (Part $p): string => 
                    route($p->isUnofficial() ? 'tracker.show' : 'official.show', ['part' => $p])
            )
            ->striped()
            ->paginated([50, 100, 250, 500])
            ->defaultPaginationPageOption(50);
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.part.weekly');
    }
}
