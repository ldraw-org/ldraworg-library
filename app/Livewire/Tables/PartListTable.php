<?php

namespace App\Livewire\Tables;

use App\Enums\License;
use App\Enums\PartCategory;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Models\Part\Part;
use App\Models\User;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\Constraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\Operators\Operator;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsEmptyOperator;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
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
            ->query(Part::with('votes', 'official_part', 'unofficial_part'))
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
            ->recordUrl(fn (Part $p) => route('parts.show', ['part' => $p]));
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
            TernaryFilter::make('library_status')
                ->label('Library Status')
                ->nullable()
                ->attribute('part_release_id')
                ->placeholder('All parts')
                ->trueLabel('Official Parts')
                ->falseLabel('Unofficial Parts')
                ->queries(
                    true: fn (Builder $query) => $query->official(),
                    false: fn (Builder $query) => $query->unofficial(),
                    blank: fn (Builder $query) => $query,
                ),
            SelectFilter::make('part_status')
                ->label('Part Status')
                ->multiple()
                ->options(PartStatus::trackerStatusOptions()),
            SelectFilter::make('category')
                ->options(PartCategory::options())
                ->searchable()
                ->preload()
                ->multiple(),
            SelectFilter::make('type')
                ->label('!LDRAW_ORG Type')
                ->options(PartType::options())
                ->preload()
                ->multiple(),
            QueryBuilder::make()
                ->constraintPickerColumns(['md' => 2, 'xl' => 4])
                ->constraints([
                    // TODO: Third Party
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
                    SelectConstraint::make('hist_user')
                        ->label('History Author')
                        ->relationship(name: 'history', titleAttribute: 'user_id')
                        ->options(User::whereHas('part_history')->pluck('name', 'id'))
                        ->multiple()
                        ->searchable(),
                    SelectConstraint::make('type')
                        ->label('!LDRAW_ORG Type')
                        ->options(PartType::options())
                        ->multiple(),
                    SelectConstraint::make('type_qualifier')
                        ->label('Alias/Physical Colour/Flex Section')
                        ->options(PartTypeQualifier::options())
                        ->multiple(),
                    SelectConstraint::make('category')
                        ->options(PartCategory::options())
                        ->multiple(),
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
                    SelectConstraint::make('license')
                        ->options(License::options())
                        ->multiple(),
                    TextConstraint::make('help')
                        ->relationship(name: 'help', titleAttribute: 'text'),
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
                    Constraint::make('is_fix')
                        ->label('Is Fix')
                        ->icon('heroicon-m-check-circle')
                        ->operators([
                            Operator::make('is_fix')
                                ->label(fn (bool $isInverse): string => $isInverse ? 'Not a part fix' : 'Is a part fix')
                                ->summary('Is Fix')
                                ->query(fn (Builder $query, bool $isInverse) => $query->{$isInverse ? 'whereDoesntHave' : 'whereHas'}(
                                    'official_part'
                                )),
                        ]),
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
