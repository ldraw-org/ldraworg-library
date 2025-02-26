<?php

namespace App\Livewire\Tables;

use App\Enums\PartStatus;
use App\Models\StickerSheet;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
                TextColumn::make('rebrickable')
                    ->state(fn (StickerSheet $s) => $s->rebrickable['name'] ?? '')
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
                                    ->where('part_status', PartStatus::NeedsMoreVotes);
                            }
                    ])
                    ->visible(Auth::user()?->can('part.vote.certify') ?? false),
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
                                ->whereIn('part_status', [PartStatus::AwaitingAdminReview, PartStatus::NeedsMoreVotes])
                                ->whereDoesntHave('subparts', function (Builder $q) {
                                    $q->whereIn('part_status', [PartStatus::AwaitingAdminReview, PartStatus::NeedsMoreVotes, PartStatus::ErrorsFound]);
                                });
                            }
                    ])
                    ->visible(Auth::user()?->can('part.vote.fasttrack') ?? false),
            ])
            ->persistSortInSession()
            ->persistSearchInSession()
            ->recordUrl(fn (StickerSheet $s): string => route('parts.sticker-sheet.show', $s));
    }
}
