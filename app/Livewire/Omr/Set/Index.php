<?php

namespace App\Livewire\Omr\Set;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Models\Omr\OmrModel;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table as Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {

        return $table
            ->heading('OMR Model List')
            ->query(OmrModel::query())
            ->defaultSort('set.number')
            ->emptyState(view('tables.empty', ['none' => 'None']))
            ->columns([
                ImageColumn::make('image')
                    ->state(
                        fn (OmrModel $m): string => version("images/omr/models/" . substr($m->filename(), 0, -4) . '_thumb.png')
                    )
                    ->grow(false)
                    ->extraImgAttributes(['class' => 'object-scale-down w-[35px] max-h-[75px]']),
                TextColumn::make('set.number')
                    ->label('Set Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('set.name')
                    ->label('Set Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('set.theme.name')
                    ->label('Theme')
                    ->state(fn (OmrModel $m) => $m->set->theme->displayString())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('set.year')
                    ->label('Year')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->state(fn (OmrModel $m) => $m->alt_model_name ?? 'Main Model'),
                TextColumn::make('user.author_string')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->whereRelation('user', 'name', 'like', "%{$search}%")
                            ->orWhereRelation('user', 'realname', 'like', "%{$search}%");
                    })
            ])
            ->recordUrl(
                fn (OmrModel $m): string =>
                    route('omr.sets.show', $m->set)
            )
            ->striped();
    }

    #[Layout('components.layout.omr')]
    public function render()
    {
        return view('livewire.omr.set.index');
    }
}
