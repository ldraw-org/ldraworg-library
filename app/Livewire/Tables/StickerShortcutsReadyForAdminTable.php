<?php

namespace App\Livewire\Tables;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Action;
use App\Enums\PartCategory;
use App\Enums\PartStatus;
use App\Enums\VoteType;
use App\Models\Part\Part;
use App\Models\Vote;
use App\Services\LDraw\Managers\VoteManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StickerShortcutsReadyForAdminTable extends BasicTable implements HasActions
{
    use InteractsWithActions;
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::unofficial()
                    ->where(function (Builder $q) {
                        $q->orWhere('category', PartCategory::StickerShortcut)
                            ->orWhereLike('description', 'Minifig Torso with Arms %');
                    })
                    ->whereIn('part_status', [PartStatus::AwaitingAdminReview, PartStatus::NeedsMoreVotes])
                    ->whereDoesntHave('descendants', function (Builder $q) {
                        $q->whereIn('part_status', [PartStatus::AwaitingAdminReview, PartStatus::NeedsMoreVotes, PartStatus::ErrorsFound]);
                    })
            )
            ->defaultSort('created_at', 'asc')
            ->heading('Torso/Sticker Shortcuts For Admin')
            ->columns(PartTable::columns())
            ->recordActions([
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
