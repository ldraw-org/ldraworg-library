<?php

namespace App\Livewire\Part;

use App\Enums\PartStatus;
use App\Models\Part\Part;
use App\Filament\Tables\PartTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table as Table;
use Livewire\Attributes\Layout;
use App\Livewire\Tables\BasicTable;
use Illuminate\Contracts\View\View;

class Weekly extends BasicTable
{
    public function table(Table $table): Table
    {

        return $table
            ->heading('Parts Tracker Submissions by Week')
            ->query(
                Part::with('votes', 'official_part', 'unofficial_part')
                ->unofficial()
                ->partsFolderOnly()
                ->doesntHave('official_part')
            )
            ->defaultSort('created_at', 'asc')
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
            ->recordActions(PartTable::actions())
            ->defaultGroup('week')
            ->recordUrl(
                fn (Part $p): string =>
                    route('parts.show', $p)
            );
    }

    #[Layout('components.layout.tracker')]
    public function render(): View
    {
        return view('livewire.part.weekly');
    }
}
