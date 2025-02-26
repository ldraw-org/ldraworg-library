<?php

namespace App\Livewire\Tables;

use App\Enums\EventType;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Models\Part\Part;
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
                SelectFilter::make('part_status')
                ->options(PartStatus::trackerStatusOptions())
                ->native(false)
                ->multiple()
                ->preload()
                ->label('Part Status'),
                SelectFilter::make('type')
                    ->options(PartType::options())
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
