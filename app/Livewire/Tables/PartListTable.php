<?php

namespace App\Livewire\Tables;

use App\Models\Part;
use App\Models\User;
use App\Livewire\Tables\PartTable;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\Constraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\Operators\Operator;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class PartListTable extends BasicTable
{
    #[Url]
    public $tableSearch = '';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(Part::with('votes', 'official_part'))
            ->emptyState(view('tables.empty', ['none' => 'None']))
            ->columns(PartTable::columns())
            ->actions(PartTable::actions())
            ->persistFiltersInSession()
            ->filters($this->filters(), layout: FiltersLayout::Modal)
            ->searchable()
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filters')
                    ->labeledFrom('md')
                    ->slideOver()
            )
            ->filtersFormWidth(MaxWidth::FourExtraLarge)
            ->recordUrl(fn(Part $p) => route('parts.show', ['part' => $p]));
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        if (filled($search = $this->getTableSearch())) {
            $query->searchPart($search, 'header');
        }
     
        return $query;
    }

    protected function filters(): array
    {
        return [
            SelectFilter::make('vote_sort')
                ->label('Vote Status')
                ->multiple()
                ->options([
                    '1' => 'Certified/Official',
                    '2' => 'Needs Admin Review',
                    '3' => 'Needs More Votes',
                    '5' => 'Errors Found'
                ]),
            SelectFilter::make('category')
                ->relationship('category', 'category')
                ->searchable()
                ->preload()
                ->multiple(),
            SelectFilter::make('type')
                ->label('!LDRAW_ORG Type')
                ->relationship(name: 'type', titleAttribute: 'name')
                ->preload()
                ->multiple(),
            QueryBuilder::make()
                ->constraintPickerColumns(['md' => 2, 'xl' => 4])
                ->constraints([
                    // TODO: History Author, Part Fixes, Alias, Third Party 
                    Constraint::make('part_release')
                        ->label('Library Status')
                        ->operators([
                            Operator::make('lib_status')
                                ->label(fn (bool $isInverse): string => $isInverse ? 'Unofficial' : 'Official')
                                ->summary(fn (bool $isInverse): string => $isInverse ? 'Library Status is Unofficial' : 'Library Status is Unofficial')
                                ->baseQuery(fn (Builder $query, bool $isInverse) => $isInverse ? $query->unofficial() : $query->official()),
                        ]),
                    SelectConstraint::make('vote_sort')
                        ->label('Vote Status')
                        ->options([
                            '1' => 'Certified',
                            '2' => 'Needs Admin Review',
                            '3' => 'Needs More Votes',
                            '5' => 'Errors Found'
                        ]),
                    TextConstraint::make('description'),
                    TextConstraint::make('filename')
                        ->label('Part Name'),
                    RelationshipConstraint::make('user')
                        ->label('Author')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->getOptionLabelFromRecordUsing(fn (User $u) => $u->authorString)
                                ->preload()
                                ->searchable()
                                ->multiple(),
                        ),
                    RelationshipConstraint::make('type')
                        ->label('!LDRAW_ORG Type')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->preload()
                                ->multiple(),
                        ),
                    RelationshipConstraint::make('category')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('category')
                                ->preload()
                                ->multiple(),
                        ),
                    RelationshipConstraint::make('keywords')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('keyword')
                                ->multiple(),
                        ),
                    SelectConstraint::make('bfc')
                        ->label('BFC Status')
                        ->options([
                            'CW' => 'CW',
                            'CCW' => 'CCW',
                        ])
                        ->multiple(),
                    RelationshipConstraint::make('license')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->preload()
                                ->multiple(),
                        ),
                    TextConstraint::make('help')
                        ->relationship(name: 'help', titleAttribute: 'help'),
                    DateConstraint::make('history_date')
                        ->relationship(name: 'history', titleAttribute: 'created_at'),
                    TextConstraint::make('history_comment')
                        ->relationship(name: 'history', titleAttribute: 'comment'),
                    TextConstraint::make('cmdline'),
                    TextConstraint::make('body')
                        ->relationship(name: 'body', titleAttribute: 'body'),
                    BooleanConstraint::make('is_pattern'),
                    BooleanConstraint::make('is_composite'),
                    BooleanConstraint::make('is_dual_mould'),
                    RelationshipConstraint::make('base_part')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('filename')
                                ->getOptionLabelFromRecordUsing(fn (Part $p) => "{$p->name()} - {$p->description}")
                                ->searchable()
                                ->multiple(),
                        )
                        ->emptyable(),
                    RelationshipConstraint::make('subparts')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('filename')
                                ->searchable()
                                ->getOptionLabelFromRecordUsing(fn (Part $p) => "{$p->name()} - {$p->description}")
                                ->multiple(),
                        )
                        ->emptyable(),
                    RelationshipConstraint::make('parents')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('filename')
                                ->searchable()
                                ->getOptionLabelFromRecordUsing(fn (Part $p) => "{$p->name()} - {$p->description}")
                                ->multiple(),
                        )
                        ->emptyable(),
                ]),
        ];
    }
}
