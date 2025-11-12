<?php

namespace App\Livewire\Tables;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Enums\EventType;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Models\Part\Part;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PartsReadyForUserTable extends BasicTable implements HasActions
{
    use InteractsWithActions;
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::with('votes', 'official_part')
                    ->unofficial()
                    ->partsFolderOnly()
                    ->whereHas('descendantsAndSelf', fn (Builder $query) => 
                        $query->whereIn('part_status', [
                            PartStatus::Needs1MoreVote,
                            PartStatus::Needs2MoreVotes,
                        ])
                        ->whereDoesntHave('votes', fn (Builder $v) => $v->where('user_id', Auth::user()->id))
                    )
            )
            ->defaultSort('created_at', 'asc')
            ->heading('Parts Ready For My Vote')
            ->columns(PartTable::columns())
            ->filters([
                TernaryFilter::make('exclude_fixes')
                    ->label('Fix Status')
                    ->placeholder('All Parts')
                    ->trueLabel('Exclude official part fixes')
                    ->falseLabel('Only official part fixes')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->isFix(),
                        false: fn (Builder $query): Builder => $query->isNotFix(),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordUrl(fn (Part $p): string => route('parts.show', $p))
            ->queryStringIdentifier('partReadyForUser');
    }

}
