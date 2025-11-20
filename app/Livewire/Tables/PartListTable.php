<?php

namespace App\Livewire\Tables;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use App\Enums\LibraryIcon;
use App\Enums\License;
use App\Enums\PartCategory;
use App\Enums\PartError;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Models\Part\Part;
use App\Models\User;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\Constraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\Operators\Operator;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint\Operators\IsOperator;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class PartListTable extends BasicTable
{
    use InteractsWithActions;

    /**
     * @var array<string, mixed> | null
     */
    #[Url]
    public ?array $tableFilters = null;

    /**
     * @var ?string
     */
    #[Url]
    public $tableSearch = '';

    public function table(Table $table): Table
    {
        return $table
            ->query(Part::with('votes', 'official_part', 'unofficial_part'))
            ->columns(PartTable::columns())
            ->recordActions(PartTable::actions())
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
            ->filtersFormWidth(Width::FourExtraLarge)
            ->recordUrl(fn (Part $p) => route('parts.show', ['part' => $p]));
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        if (filled($search = $this->getTableSearch())) {
            $query->searchHeader($search);
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
            Filter::make('my_votes')
                ->label('Exclude parts with my vote')
                ->query(fn (Builder $query) => $query->whereDoesntHave('votes', fn (Builder $query2) => $query2->where('user_id', Auth::user()?->id)))
                ->visible(fn () => Auth::check()),
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
                        ->label('Part Name')
                        ->icon(LibraryIcon::File->value),
                    RelationshipConstraint::make('user')
                        ->label('Author')
                        ->icon(LibraryIcon::AuthorConstraint->value)
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
                        ->icon(LibraryIcon::AuthorConstraint->value)
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
                    RelationshipConstraint::make('release')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->multiple(),
                        ),
                    SelectConstraint::make('category')
                        ->options(PartCategory::options())
                        ->searchable()
                        ->icon(LibraryIcon::CategoryConstraint->value)
                        ->multiple(),
                    SelectConstraint::make('part_errors')
                        ->options(PartError::class)
                        ->icon(LibraryIcon::Error->value)
                        ->multiple()
                        ->operators([
                            IsOperator::make('error')
                                ->query(function (Builder $query, bool $isInverse, IsOperator $operator) {
                                    $values = $operator->getSettings()['values'];
                                    $query->where(function (Builder $query_inner) use ($values, $isInverse) {
                                        foreach ($values as $value) {
                                            $query_inner->{$isInverse ? 'orDoesntHaveMessage' : 'orHasMessage'}($value);
                                        }
                                    });
                                })
                        ]),
                    RelationshipConstraint::make('keywords')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('keyword')
                                ->multiple(),
                        )
                        ->icon(LibraryIcon::KeywordsConstraint->value),
                    SelectConstraint::make('bfc')
                        ->label('BFC Status')
                        ->icon(LibraryIcon::ViewerBfc->value)
                        ->options([
                            'CW' => 'CW',
                            'CCW' => 'CCW',
                        ])
                        ->multiple(),
                    SelectConstraint::make('license')
                        ->options(License::options())
                        ->icon(LibraryIcon::License->value)
                        ->multiple(),
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
                        ->icon(LibraryIcon::BooleanConstraint->value)
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
                                ->getOptionLabelFromRecordUsing(fn (Part $p) => "{$p->meta_name} - {$p->description}")
                                ->searchable()
                                ->multiple(),
                        )
                        ->icon(LibraryIcon::ViewerStudLogo->value)
                        ->emptyable(),
                    RelationshipConstraint::make('subparts')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('filename')
                                ->searchable()
                                ->getOptionLabelFromRecordUsing(fn (Part $p) => "{$p->meta_name} - {$p->description}")
                                ->multiple(),
                        )
                        ->icon(LibraryIcon::ViewerStudLogo->value)
                        ->emptyable(),
                    RelationshipConstraint::make('parents')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('filename')
                                ->searchable()
                                ->getOptionLabelFromRecordUsing(fn (Part $p) => "{$p->meta_name} - {$p->description}")
                                ->multiple(),
                        )
                        ->icon(LibraryIcon::ViewerStudLogo->value)
                        ->emptyable(),
                ]),
        ];
    }
}
