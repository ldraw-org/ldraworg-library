<?php

namespace App\Livewire\Tables;

use App\Enums\PartStatus;
use App\Models\Part\Part;
use Filament\Tables\Table;

class PartReadyForAdminTable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::with(['votes', 'official_part'])
                    ->partsFolderOnly()
                    ->where('ready_for_admin', true)
                    ->whereNotIn('part_status', [PartStatus::Certified, PartStatus::Official])
            )
            ->defaultSort('created_at', 'asc')
            ->heading('Parts Ready For Admin')
            ->columns(PartTable::columns())
            ->recordUrl(fn (Part $p): string => route('parts.show', $p))
            ->queryStringIdentifier('readyForAdmin');
    }

}
