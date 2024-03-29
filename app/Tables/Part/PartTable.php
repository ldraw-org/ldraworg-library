<?php

namespace App\Tables\Part;

use App\Models\Part;
use App\Tables\Filters\AuthorFilter;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class PartTable
{
    public static function table(Table $table, bool $official = true): Table
    {
        return $table
            ->query(
                Part::when($official,
                    fn(Builder $q) => $q->official(),
                    fn(Builder $q) => $q->unofficial()
                )
            )
            ->defaultSort(fn (Builder $q) => $q->orderBy('vote_sort', 'asc')->orderBy('part_type_id', 'asc')->orderBy('description', 'asc'))
            ->emptyState(view('tables.empty', ['none' => 'None']))
            ->columns(self::columns())
            ->filters([
                SelectFilter::make('vote_sort')
                    ->options([
                        '1' => 'Certified',
                        '2' => 'Needs Admin Review',
                        '3' => 'Needs More Votes',
                        '5' => 'Errors Found'
                    ])
                    ->native(false)
                    ->multiple()
                    ->preload()
                    ->label('Unofficial Status')
                    ->visible(!$official),
                AuthorFilter::make('user_id'),
                SelectFilter::make('part_type_id')
                    ->relationship('type', 'name')
                    ->native(false)
                    ->multiple()
                    ->preload()
                    ->label('Part Type'),
                SelectFilter::make('part_license_id')
                    ->relationship('license', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->label('Part License'),
                TernaryFilter::make('exclude_fixes')
                    ->label('Fix Status')
                    ->placeholder('All Parts')
                    ->trueLabel($official ? 'Exclude parts with active fixes' : 'Exclude official part fixes')
                    ->falseLabel($official ? 'Only parts with active fixes' : 'Only official part fixes')
                    ->queries(
                        true: fn (Builder $q) => $q->doesntHave($official ? 'unofficial_part' : 'official_part'),
                        false: fn (Builder $q) => $q->has($official ? 'unofficial_part' : 'official_part'),
                        blank: fn (Builder $q) => $q,
                    ),
            ], layout: FiltersLayout::AboveContent)
            ->actions(self::actions())
            ->recordUrl(
                fn (Part $p): string => 
                    route($p->isUnofficial() ? 'tracker.show' : 'official.show', ['part' => $p])
            )
            ->striped()
            ->paginated([50, 100, 250, 500])
            ->defaultPaginationPageOption(50);
    }

    public static function columns(): array
    {
        return [
            Split::make([
                ImageColumn::make('image')
                    ->state( 
                        fn (Part $p): string => asset("images/library/{$p->libFolder()}/" . substr($p->filename, 0, -4) . '_thumb.png')
                    )
                    ->grow(false)
                    ->extraImgAttributes(['class' => 'object-scale-down w-[35px] max-h-[75px]']),
                Stack::make([
                    TextColumn::make('filename')
                    ->weight(FontWeight::Bold)
                    ->sortable(),
                TextColumn::make('description')
                    ->sortable(),
                ])->alignment(Alignment::Start),
                ViewColumn::make('vote_sort')
                    ->view('tables.columns.part-status')
                    ->sortable()
                    ->grow(false)
                    ->label('Status')
            ])->from('md')
        ];
    }

    public static function actions(): array
    {
        return [
            Action::make('download')
                ->url(fn(Part $part) => route($part->isUnofficial() ? 'unofficial.download' : 'official.download', $part->filename))
                ->button()
                ->outlined()
                ->color('info'),
            Action::make('updated')
                ->url(fn(Part $part) => route('tracker.show', $part->unofficial_part->id))
                ->label(fn(Part $part) => ' Tracker Update: ' . $part->unofficial_part->statusCode())
                ->button()
                ->outlined()
                ->visible(fn(Part $part) => !is_null($part->unofficial_part)),
        ];
    }
}