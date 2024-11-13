<?php

namespace App\Livewire\Tables;

use App\Models\Part\Part;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OfficialPartsWithErrorsTable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::whereJsonLength('part_check_messages->errors', '>', 0)->official()
            )
            ->heading('Official Parts With Errors')
            ->columns([
                ImageColumn::make('image')
                    ->state(
                        fn (Part $p): string => asset("images/library/{$p->libFolder()}/" . substr($p->filename, 0, -4) . '_thumb.png')
                    )
                    ->grow(false)
                    ->extraImgAttributes(['class' => 'object-scale-down w-[35px] max-h-[75px]']),
                TextColumn::make('filename')
                    ->sortable(),
                TextColumn::make('description')
                    ->sortable(),
                TextColumn::make('part_check_messages')
                    ->state(fn (Part $part) => implode(", ", $part->part_check_messages['errors']))
                    ->wrap()
            ])
            ->recordUrl(fn (Part $p): string => route('parts.show', $p))
            ->queryStringIdentifier('officialErrors');
    }
}
