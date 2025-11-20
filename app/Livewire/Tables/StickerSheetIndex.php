<?php

namespace App\Livewire\Tables;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Enums\PartCategory;
use App\Enums\PartStatus;
use App\Enums\Permission;
use App\Models\RebrickablePart;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * @property Table $table
 */
class StickerSheetIndex extends BasicTable implements HasActions
{
    use InteractsWithActions;
    public function table(Table $table): Table
    {
        return $table
            ->query(
                RebrickablePart::where('rb_part_category_id', '58')
                ->has('parts')
            )
            ->heading('Sticker Sheets')
            ->columns([
                TextColumn::make('number')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
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
                                $q->where('category', PartCategory::Sticker);
                            }
                    ]),
                TextColumn::make('shortcut_count')
                    ->label('Shortcuts')
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->counts([
                        'parts as shortcut_count' =>
                            function (Builder $q) {
                                $q->where('category', PartCategory::StickerShortcut);
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
                                $q->where('category', PartCategory::Sticker)
                                    ->whereIn('part_status', [PartStatus::Needs1MoreVote,PartStatus::Needs2MoreVotes]);
                            }
                    ])
                    ->visible(Auth::user()?->can(Permission::PartVoteCertify) ?? false),
                TextColumn::make('shortcut_fast_track_ready_count')
                    ->sortable()
                    ->label('Shortcuts Can Be Fast Tracked')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->counts([
                        'parts as shortcut_fast_track_ready_count' =>
                            function (Builder $q) {
                                $q->where('category', PartCategory::StickerShortcut)
                                ->unofficial()
                                ->whereIn('part_status', [PartStatus::AwaitingAdminReview, PartStatus::Needs1MoreVote, PartStatus::Needs2MoreVotes])
                                ->whereDoesntHave('subparts', function (Builder $q) {
                                    $q->whereIn('part_status', [PartStatus::AwaitingAdminReview, PartStatus::Needs1MoreVote,  PartStatus::Needs2MoreVotes, PartStatus::ErrorsFound]);
                                });
                            }
                    ])
                    ->visible(Auth::user()?->can(Permission::PartVoteFastTrack) ?? false),
            ])
            ->persistSortInSession()
            ->persistSearchInSession()
            ->recordUrl(fn (RebrickablePart $rbPart): string => route('parts.sticker-sheet.show', $rbPart));
    }
}
