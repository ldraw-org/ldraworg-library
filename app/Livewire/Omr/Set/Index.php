<?php

namespace App\Livewire\Omr\Set;

use App\Models\Omr\Set;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table as Table;
use Livewire\Attributes\Layout;
use App\Livewire\Tables\BasicTable;
use Illuminate\Contracts\View\View;

class Index extends BasicTable
{
    public function table(Table $table): Table
    {

        return $table
            ->query(Set::with(['models', 'theme'])->has('models'))
            ->defaultSort('number')
            ->columns([
                ImageColumn::make('image')
                    ->state(fn (Set $set) => $set->mainModel()->getFirstMediaUrl('image', 'thumb'))
                    ->grow(false)
                    ->extraImgAttributes(['class' => 'object-scale-down w-[35px] max-h-[75px]']),
                TextColumn::make('number')
                    ->label('Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('theme.name')
                    ->label('Theme')
                    ->state(fn (Set $s) => $s->theme->displayString())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('year')
                    ->label('Year')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('models_count')
                    ->label('Models')
                    ->sortable()
                    ->counts('models as models_count'),
            ])
            ->recordUrl(
                fn (Set $s): string =>
                    route('omr.sets.show', $s)
            );
    }

    #[Layout('components.layout.omr')]
    public function render(): View
    {
        return view('livewire.omr.set.index');
    }
}
