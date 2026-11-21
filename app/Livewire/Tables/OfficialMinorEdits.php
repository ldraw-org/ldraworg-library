<?php

namespace App\Livewire\Tables;

use App\Filament\Tables\PartTable;
use App\Models\Part\Part;
use Filament\Tables\Table;

class OfficialMinorEdits extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::official()
                    ->where('has_minor_edit', true)
                    ->orderBy('type')
                    ->orderBy('filename')
            )
            ->columns(PartTable::columns())
            ->recordActions(PartTable::actions())
            ->recordUrl(fn (Part $p): string => route('parts.show', $p));
    }

}
