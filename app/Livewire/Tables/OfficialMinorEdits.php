<?php

namespace App\Livewire\Tables;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Models\Part\Part;
use Filament\Tables\Table;

class OfficialMinorEdits extends BasicTable implements HasActions
{
    use InteractsWithActions;
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
