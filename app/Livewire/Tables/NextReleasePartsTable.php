<?php

namespace App\Livewire\Tables;

use App\Filament\Tables\PartTable;
use App\Enums\PartStatus;
use App\Models\Part\Part;
use Filament\Tables\Table;

class NextReleasePartsTable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->heading('Parts In Next Release')
            ->description('These are the parts that currently qualify for the next update. While the parts on this list will generally be released in the next update, some of them may be manually held back by the the Library Admin for various other reasons.')
            ->query(
                Part::unofficial()
                    ->where('part_status', PartStatus::Certified)
                    ->where('can_release', true)
                    ->orderBy('type')
                    ->orderBy('filename')
            )
            ->columns(PartTable::columns())
            ->recordActions(PartTable::actions())
            ->recordUrl(fn (Part $p): string => route('parts.show', $p));
    }

}
