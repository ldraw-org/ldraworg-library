<?php

namespace App\Livewire\Tables;

use App\Livewire\Tables\BasicTable;
use App\Models\StickerSheet;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property Table $table
 */
class StickerSheetIndex extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(StickerSheet::has('parts'))
            ->heading('Sticker Sheets')
            ->columns([
                TextColumn::make('number')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('rebrickable_part.name')
                    ->label('Description')
                    ->grow()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sticker_count')
                    ->label('Stickers')
                    ->alignment(Alignment::Center)
                    ->sortable()
                    ->counts([
                        'parts as sticker_count' => 
                            function (Builder $q) { 
                                $q->whereRelation('category', 'category', 'Sticker');
                            }
                    ]),
                TextColumn::make('shortcut_count')
                    ->label('Shortcuts')
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->counts([
                        'parts as shortcut_count' => 
                            function (Builder $q) { 
                                $q->whereRelation('category', 'category', 'Sticker Shortcut');
                            }
                    ]),
                TextColumn::make('stickers_need_votes_count')
                    ->sortable()
                    ->label('Stickers Need Votes')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->counts([
                        'parts as stickers_need_votes_count' => 
                            function (Builder $q) { 
                                $q->whereRelation('category', 'category', 'Sticker')
                                    ->where('vote_sort', '3');
                            }
                    ])
                    ->visible(auth()->user()?->can('part.vote.certify') ?? false),
                TextColumn::make('shortcut_fast_track_ready_count')
                    ->sortable()
                    ->label('Shortcuts Can Be Fast Tracked')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->counts([
                        'parts as shortcut_fast_track_ready_count' => 
                            function (Builder $q) { 
                                $q->whereRelation('category', 'category', 'Sticker Shortcut')
                                ->whereNull('part_release_id')
                                ->whereBetween('vote_sort', [2, 4])
                                ->whereDoesntHave('subparts', function (Builder $q) {
                                    $q->where('vote_sort', '>', '1');
                                }); 
                            }
                    ])
                    ->visible(auth()->user()?->can('part.vote.fasttrack') ?? false),
            ])
            ->persistSortInSession()
            ->persistSearchInSession()
            ->recordUrl(fn (StickerSheet $s): string => route('sticker-sheet.show', $s));
    }
}
