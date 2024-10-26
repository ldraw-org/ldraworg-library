<?php

namespace App\Livewire\Tables;

use App\Models\Part;
use App\Filament\Part\Tables\Filters\AuthorFilter;
use App\Filament\Part\Tables\PartTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PartListTable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(Part::query())
            ->emptyState(view('tables.empty', ['none' => 'None']))
            ->columns(PartTable::columns())
            ->actions(PartTable::actions())
            ->filters($this->filters(), layout: FiltersLayout::AboveContent)
            ->groups([
                Group::make('part_release_id')
                    ->label('Library Status')
                    ->getTitleFromRecordUsing(fn (Part $p):string => $p->isUnofficial() ? 'Unofficial' : 'Official')
                    ->getKeyFromRecordUsing(fn (Part $p): string => $p->isUnofficial() ? 'B' : 'A'),
            ])
            ->defaultGroup('part_release_id')
            ->defaultSort('description')
            ->searchable()
            ->recordUrl(
                fn (Part $p): string =>
                    route($p->isUnofficial() ? 'tracker.show' : 'official.show', ['part' => $p])
            )
            ->recordClasses(fn (Part $p) => !$p->isUnofficial() ? '!bg-green-300' : '');
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        $scope = $this->getTableFilterState('scope');
        if (filled($search = $this->getTableSearch())) {
            $query->searchPart($search, $scope['value']);
        }
     
        return $query;
    }

    protected function filters(): array
    {
        return [
            TernaryFilter::make('library-status')
                ->placeholder('All Parts')
                ->trueLabel('Official')
                ->falseLabel('Unofficial')
                ->queries(
                    true: fn (Builder $q) => $q->whereNotNull('part_release_id'),
                    false: fn (Builder $q) => $q->whereNull('part_release_id'),
                    blank: fn (Builder $q) => $q,
                ),
            SelectFilter::make('vote_sort')
                ->options([
                    '1' => 'Certified',
                    '2' => 'Needs Admin Review',
                    '3' => 'Needs More Votes',
                    '5' => 'Errors Found'
                ])
                ->multiple()
                ->preload()
                ->label('Unofficial Status'),
            AuthorFilter::make('user_id'),
            SelectFilter::make('part_type_id')
                ->relationship('type', 'name')
                ->multiple()
                ->preload()
                ->label('Part Type'),
            SelectFilter::make('part_category_id')
                ->relationship('category', 'category')
                ->multiple()
                ->preload()
                ->label('Category'),
            SelectFilter::make('keywords')
                ->relationship('keywords', 'keyword')
                ->multiple()
                ->label('Keywords'),
            SelectFilter::make('part_license_id')
                ->relationship('license', 'name')
                ->searchable()
                ->preload()
                ->label('Part License'),
            TernaryFilter::make('part_class')
                ->label('Part Class')
                ->placeholder('All Parts')
                ->trueLabel('Third Party Parts')
                ->falseLabel('Alias Parts')
                ->queries(
                    true: fn (Builder $q) => $q->where('description', 'LIKE', '|%'),
                    false: fn (Builder $q) => $q->whereRelation('type_qualifier', 'type', 'Alias'),
                    blank: fn (Builder $q) => $q,
                ),
            TernaryFilter::make('exclude_fixes')
                ->label('Fix Status')
                ->placeholder('All Parts')
                ->trueLabel('Exclude official part fixes')
                ->falseLabel('Only official part fixes')
                ->queries(
                    true: fn (Builder $q) => $q->doesntHave('official_part'),
                    false: fn (Builder $q) => $q->has('official_part'),
                    blank: fn (Builder $q) => $q,
                ),
            SelectFilter::make('scope')
                ->label('Search Scope')
                ->options([
                    'filename' => 'Filename only',
                    'description' => 'Filename and description',
                    'header' => 'File header',
                    'file' => 'Entire file (very slow)'
                ])
                ->default('header')
                ->selectablePlaceholder(false)
                ->indicateUsing(null)
                ->query(fn (Builder $query) => $query),

        ];
    }
}
