<?php

namespace App\Livewire\Part;

use App\Enums\PartStatus;
use App\Models\Part\Part;
use App\Livewire\Tables\PartTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
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
            ->query(Part::with('votes', 'official_part', 'unofficial_part')
                ->unofficial()
                ->partsFolderOnly()
                ->doesntHave('official_part')
            )
            ->defaultSort('created_at', 'asc')
            ->emptyState(view('tables.empty', ['none' => 'None']))
            ->columns(PartTable::columns())
            ->groups([
                Group::make('week')
                    ->date(),
            ])
            ->filters([
                SelectFilter::make('part_status')
                ->label('Part Status')
                ->multiple()
                ->options(PartStatus::trackerStatusOptions()),
            ])
            ->actions(PartTable::actions())
            ->defaultGroup('week')
            ->recordUrl(
                fn (Part $p): string =>
                    route('parts.show', $p)
            );
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.part.weekly');
    }
}
