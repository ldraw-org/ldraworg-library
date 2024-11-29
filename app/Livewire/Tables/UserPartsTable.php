<?php

namespace App\Livewire\Tables;

use App\Enums\EventType;
use App\Models\Part\Part;
use App\Livewire\Tables\PartTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserPartsTable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::with('votes', 'official_part')
                    ->unofficial()
                    ->where(
                        fn (Builder $query) =>
                        $query->orWhere(fn (Builder $query2): Builder => $query2->doesntHave('official_part')->where('user_id', Auth::user()->id))
                            ->orWhereHas('events', fn (Builder $query2): Builder => $query2->whereNull('part_release_id')->where('user_id', Auth::user()->id)->where('event_type', EventType::Submit))
                    )
            )
            ->defaultSort('created_at', 'desc')
            ->heading('MySubmits')
            ->columns(PartTable::columns())
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
                ->label('Unofficial Status'),
                SelectFilter::make('part_type_id')
                    ->relationship('type', 'name')
                    ->native(false)
                    ->multiple()
                    ->preload()
                    ->label('Part Type'),
                TernaryFilter::make('exclude_fixes')
                    ->label('Fix Status')
                    ->placeholder('All Parts')
                    ->trueLabel('Exclude official part fixes')
                    ->falseLabel('Only official part fixes')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->doesntHave('official_part'),
                        false: fn (Builder $query): Builder => $query->has('official_part'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordUrl(fn (Part $p): string => route('parts.show', $p))
            ->queryStringIdentifier('userParts');
    }

}
