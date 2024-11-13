<?php

namespace App\Filament\Part\Tables;

use App\Models\Part;
use App\Filament\Tables\Filters\AuthorFilter;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class PartTable
{
    public static function table(Table $table, bool $official = true): Table
    {
        return $table
            ->query(
                Part::when(
                    $official,
                    fn (Builder $q) => $q->official(),
                    fn (Builder $q) => $q->unofficial()
                )
            )
            ->defaultSort(fn (Builder $q) => $q->orderBy('vote_sort', 'asc')->orderBy('part_type_id', 'asc')->orderBy('description', 'asc'))
            ->columns(self::columns())
            ->filters(self::filters($official), layout: FiltersLayout::AboveContent)
            ->actions(self::actions())
            ->recordUrl(
                fn (Part $p): string =>
                    route('parts.show', ['part' => $p])
            );
    }

    public static function columns(): array
    {
        return [
            Split::make([
                ImageColumn::make('image')
                    ->state(
                        fn (Part $p): string => version("images/library/{$p->libFolder()}/" . substr($p->filename, 0, -4) . '_thumb.png')
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
                ->url(fn (Part $part) => route($part->isUnofficial() ? 'unofficial.download' : 'official.download', $part->filename))
                ->button()
                ->outlined()
                ->color('info'),
            Action::make('download')
                ->label('Download zip')
                ->url(fn (Part $part) => route('unofficial.download.zip', str_replace('.dat', '.zip', $part->filename)))
                ->button()
                ->outlined()
                ->color('info')
                ->visible(fn (Part $part) => $part->isUnofficial() && $part->type->folder == 'parts/'),
            Action::make('updated')
                ->url(fn (Part $part) => route('parts.show', $part->unofficial_part->id))
                ->label(fn (Part $part) => ' Tracker Update: ' . $part->unofficial_part->statusCode())
                ->button()
                ->outlined()
                ->visible(fn (Part $part) => !is_null($part->unofficial_part)),
        ];
    }

    public static function filters(bool $official = true): array
    {
        return [
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
            SelectFilter::make('part_category_id')
                ->relationship('category', 'category')
                ->native(false)
                ->multiple()
                ->preload()
                ->label('Category'),
            SelectFilter::make('keywords')
                ->relationship('keywords', 'keyword')
                ->native(false)
                ->multiple()
                ->label('Keywords'),
            SelectFilter::make('part_license_id')
                ->relationship('license', 'name')
                ->native(false)
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
                ->trueLabel($official ? 'Exclude parts with active fixes' : 'Exclude official part fixes')
                ->falseLabel($official ? 'Only parts with active fixes' : 'Only official part fixes')
                ->queries(
                    true: fn (Builder $q) => $q->doesntHave($official ? 'unofficial_part' : 'official_part'),
                    false: fn (Builder $q) => $q->has($official ? 'unofficial_part' : 'official_part'),
                    blank: fn (Builder $q) => $q,
                ),
        ];
    }
}
