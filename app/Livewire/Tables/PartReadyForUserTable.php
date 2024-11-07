<?php

namespace App\Livewire\Tables;

use App\Models\Part;
use App\Models\User;
use App\Filament\Part\Tables\PartTable;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PartReadyForUserTable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::with(['votes', 'events', 'official_part', 'descendantsAndSelf', 'user'])->unofficial()
                // Parts folder only
                ->whereHas('type', fn (Builder $q) => $q->where('folder', 'parts/'))
                // No holds
                ->whereDoesntHave('descendantsAndSelf', function ($q) {
                    $q->where('vote_sort', '5');
                })
                // At least one part in the chain with
                ->whereHas('descendantsAndSelf', function (Builder $q) {
                    // Only Needs More Votes
                    $q->where('vote_sort', 3)
                    // No votes from user
                    ->whereDoesntHave('votes', fn (Builder $qu) => $qu->where('user_id', Auth::user()->id))
                    // No submit events from user
                    ->whereDoesntHave(
                        'events',
                        fn (Builder $qu) =>
                        $qu->where('user_id', Auth::user()->id)->whereRelation('part_event_type', 'slug', 'submit')->whereNull('part_release_id')
                    )
                    // Not authored by user unless a fix
                    ->where(function (Builder $q) {
                        $q->orHas('official_part')
                        ->orWhere(
                            fn (Builder $qu) =>
                            $qu->doesntHave('official_part')->where('user_id', '<>', Auth::user()->id)
                        );
                    });
                }, '>=', 1)
            )

            ->filters([
                Filter::make('exclude_author')
                    ->form([
                        Select::make('author')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name'
                            )
                            ->getOptionLabelFromRecordUsing(fn (User $u) => $u->authorString)
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->label('Author'),
                        Toggle::make('exclude')
                            ->label('Exclude')
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['author'] && $data['exclude'],
                                fn (Builder $q) => $q->where('user_id', '<>', $data['author']),
                            )
                            ->when(
                                $data['author'] && !$data['exclude'],
                                fn (Builder $q) => $q->where('user_id', $data['author']),
                            );
                    })
            ])
            ->defaultSort('created_at', 'asc')
            ->heading('Parts Ready For Your Vote')
            ->description('This table show parts where the part and/or the parts in the subfile chain can recieve a vote from you')
            ->columns(PartTable::columns())
            ->recordUrl(fn (Part $p): string => route('parts.show', $p))
            ->queryStringIdentifier('readyForUser')
            ->persistFiltersInSession();
        ;
    }
}
