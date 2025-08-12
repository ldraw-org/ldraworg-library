<?php

namespace App\Livewire\Tables;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Enums\LibraryIcon;
use App\Enums\License;
use App\Enums\PartCategory;
use App\Enums\PartError;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Models\Part\Part;
use App\Models\User;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
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
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class CategoryStatus extends BasicTable implements HasActions
{
    use InteractsWithActions;
    #[Url]
    public $tableSearch = '';

    public function table(Table $table): Table
    {
        return $table
            ->query(Part::unofficial()->partsFolderOnly())
            ->defaultGroup('category')
            ->groupingSettingsHidden()
            ->groups([
                Group::make('category')
                    ->getTitleFromRecordUsing(fn (Part $part) => $part->category->value ?? ''),
            ])
            ->emptyState(view('tables.empty', ['none' => 'None']))
            ->columns([
                IconColumn::make('part_status')
                    ->icon('mdi-square-rounded')
                    ->color(fn (Part $part) => match ($part->part_status){
                        PartStatus::Certified => 'green',
                        PartStatus::AwaitingAdminReview => 'blue',
                        PartStatus::NeedsMoreVotes => 'gray',
                        PartStatus::ErrorsFound => 'red',
                        default => ''
                    })
                    ->grow(false)
                    ->summarize([
                        Count::make()->label('Certified')->query(fn ($query) => $query->where('part_status', PartStatus::Certified))->icons(),
                        Count::make()->label('Awaiting Admin Review')->query(fn ($query) => $query->where('part_status', PartStatus::AwaitingAdminReview))->icons(),
                        Count::make()->label('Needs More Votes')->query(fn ($query) => $query->where('part_status', PartStatus::NeedsMoreVotes))->icons(),
                        Count::make()->label('ErrorsFound')->query(fn ($query) => $query->where('part_status', PartStatus::ErrorsFound))->icons()
                    ]),                
            ])
            ->groupsOnly()
            ->recordUrl(
                null//fn (Part $p): string => dd($p)//tableFilters[category][values][0]=Brick route('posts.edit', ['record' => $record]),
            )
            ->openRecordUrlInNewTab();
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
                    SelectConstraint::make('category')
                        ->options(PartCategory::options())
                        ->icon(LibraryIcon::CategoryConstraint->value)
                        ->multiple(),
                    SelectConstraint::make('part_errors')
                        ->options(PartError::options())
                        ->icon(LibraryIcon::Error->value)
                        ->multiple()
                        ->operators([
                            IsOperator::make('error')
                                ->query(function (Builder $query, bool $isInverse, IsOperator $operator) {
                                    $values = $operator->getSettings()['values'];
                                    $query->where(function (Builder $query_inner) use ($values, $isInverse) {
                                        foreach ($values as $value) {
                                            $query_inner->{$isInverse ? 'orDoesntHaveError' : 'orHasError'}($value);
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
                                ->getOptionLabelFromRecordUsing(fn (Part $p) => "{$p->name()} - {$p->description}")
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
                                ->getOptionLabelFromRecordUsing(fn (Part $p) => "{$p->name()} - {$p->description}")
                                ->multiple(),
                        )
                        ->icon(LibraryIcon::ViewerStudLogo->value)
                        ->emptyable(),
                    RelationshipConstraint::make('parents')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('filename')
                                ->searchable()
                                ->getOptionLabelFromRecordUsing(fn (Part $p) => "{$p->name()} - {$p->description}")
                                ->multiple(),
                        )
                        ->icon(LibraryIcon::ViewerStudLogo->value)
                        ->emptyable(),
                ]),
        ];
    }
}
