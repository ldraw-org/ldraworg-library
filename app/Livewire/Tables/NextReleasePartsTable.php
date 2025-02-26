<?php

namespace App\Livewire\Tables;

use App\Enums\PartStatus;
use App\Models\Part\Part;
use Filament\Tables\Table;

class NextReleasePartsTable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::unofficial()
                    ->where('part_status', PartStatus::Certified)
                    ->where('can_release', true)
                    ->orderBy('type')
                    ->orderBy('filename')
            )
            ->emptyState(view('tables.empty', ['none' => 'None']))
            ->columns(PartTable::columns())
            ->actions(PartTable::actions())
            ->recordUrl(fn (Part $p): string => route('parts.show', $p));
    }

}
