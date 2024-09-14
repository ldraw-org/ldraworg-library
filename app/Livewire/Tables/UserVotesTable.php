<?php

namespace App\Livewire\Tables;

use App\Models\Vote;
use App\Models\VoteType;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UserVotesTable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Auth::user()->votes()->getQuery()
            )
            ->defaultSort('created_at', 'desc')
            ->heading('My Votes')
            ->columns([
                Split::make([
                    ViewColumn::make('vote_type_code')
                    ->view('tables.columns.vote-status')
                    ->sortable()
                    ->grow(false)
                    ->label('My Vote'),
                    ImageColumn::make('image')
                        ->state(
                            fn (Vote $v): string => version('images/library/unofficial/' . substr($v->part->filename, 0, -4) . '_thumb.png')
                        )
                        ->grow(false)
                        ->extraImgAttributes(['class' => 'object-scale-down w-[35px] max-h-[75px]']),
                    Stack::make([
                        TextColumn::make('part.filename')
                            ->label('Filename')
                            ->sortable(),
                        TextColumn::make('part.description')
                            ->label('Description')
                            ->sortable(),
                    ]),
                    ViewColumn::make('part.vote_status')
                        ->view('tables.columns.event-part-status')
                        ->grow(false)
                        ->sortable()
                        ->label('Status'),
                ])
            ])
            ->filters([
                SelectFilter::make('vote_type_code')
                    ->options(VoteType::whereIn('code', ['A', 'C', 'T', 'H'])->ordered()->pluck('name', 'code'))
                    ->preload()
                    ->multiple()
                    ->label('My Vote'),
            ])
            ->recordUrl(
                fn (Vote $v): string =>
                    route('tracker.show', ['part' => $v->part])
            )
            ->queryStringIdentifier('userVotes');
    }

}
