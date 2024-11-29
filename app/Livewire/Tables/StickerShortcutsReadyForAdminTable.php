<?php

namespace App\Livewire\Tables;

use App\Enums\VoteType;
use App\Models\Part\Part;
use App\Models\Vote;
use App\Livewire\Tables\PartTable;
use App\LDraw\VoteManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StickerShortcutsReadyForAdminTable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::unofficial()
                    ->whereRelation('category', 'category', 'Sticker Shortcut')
                    ->whereBetween('vote_sort', [2, 4])
                    ->whereDoesntHave('descendants', fn (Builder $q) => $q->where('vote_sort', '>', 1))
            )
            ->defaultSort('created_at', 'asc')
            ->heading('Sticker Shortcuts For Admin')
            ->columns(PartTable::columns())
            ->actions([
                Action::make('Fast Track')
                    ->action(function (Part $p) {
                        $vm = new VoteManager();
                        $vm->castVote($p, auth()->user(), VoteType::AdminFastTrack);
                    })
                    ->button()
                    ->outlined()
                    ->visible(fn (Part $p) => auth()->user()?->can('vote', [Vote::class, $p, VoteType::AdminFastTrack]))

            ])
            ->recordUrl(fn (Part $p): string => route('parts.show', $p))
            ->queryStringIdentifier('stickerShortcutsReadyForAdmin');
    }

}
