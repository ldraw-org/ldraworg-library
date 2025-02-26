<?php

namespace App\Livewire\Tables;

use App\Enums\PartStatus;
use App\Enums\VoteType;
use App\Models\Part\Part;
use App\Models\Vote;
use App\LDraw\VoteManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StickerShortcutsReadyForAdminTable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::unofficial()
                    ->whereRelation('category', 'category', 'Sticker Shortcut')
                    ->whereIn('part_status', [PartStatus::AwaitingAdminReview, PartStatus::NeedsMoreVotes])
                    ->whereDoesntHave('descendants', function (Builder $q) {
                        $q->whereIn('part_status', [PartStatus::AwaitingAdminReview, PartStatus::NeedsMoreVotes, PartStatus::ErrorsFound]);
                    })
            )
            ->defaultSort('created_at', 'asc')
            ->heading('Sticker Shortcuts For Admin')
            ->columns(PartTable::columns())
            ->actions([
                Action::make('Fast Track')
                    ->action(function (Part $p) {
                        $vm = new VoteManager();
                        $vm->castVote($p, Auth::user(), VoteType::AdminFastTrack);
                    })
                    ->button()
                    ->outlined()
                    ->visible(fn (Part $p) => Auth::user()?->can('vote', [Vote::class, $p, VoteType::AdminFastTrack]))

            ])
            ->recordUrl(fn (Part $p): string => route('parts.show', $p))
            ->queryStringIdentifier('stickerShortcutsReadyForAdmin');
    }

}
