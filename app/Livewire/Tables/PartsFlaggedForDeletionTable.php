<?php

namespace App\Livewire\Tables;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Models\Part\Part;
use Filament\Tables\Table;

class PartsFlaggedForDeletionTable extends BasicTable implements HasActions
{
    use InteractsWithActions;
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::where('delete_flag', true)->orderby('filename')
            )
            ->heading('Parts Flagged For Deletion')
            ->columns(PartTable::columns())
            ->recordUrl(fn (Part $p): string => route('parts.show', $p))
            ->queryStringIdentifier('deleteFlagged');
    }
}
