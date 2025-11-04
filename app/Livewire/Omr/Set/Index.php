<?php

namespace App\Livewire\Omr\Set;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Models\Omr\Set;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table as Table;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component implements HasSchemas, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {

        return $table
            ->heading('OMR Model List')
            ->query(Set::with(['models', 'theme']))
            ->defaultSort('number')
            ->columns([
                ImageColumn::make('image')
                    ->state(fn (Set $set) => $set->mainModel()?->getFirstMediaUrl('image', 'thumb') ?? blank_image_url())
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
    public function render()
    {
        return view('livewire.omr.set.index');
    }
}
