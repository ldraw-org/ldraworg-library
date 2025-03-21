<?php

namespace App\Livewire\Tables;

use App\Enums\VoteType;
use App\Models\Vote;
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
                Vote::with('part', 'part.votes', 'part.official_part')->where('user_id', Auth::user()->id)
            )
            ->defaultSort('created_at', 'desc')
            ->heading('My Votes')
            ->columns([
                Split::make([
                    ImageColumn::make('image')
                        ->state(
                            fn (Vote $v): string => version("images/library/{$v->part->imageThumbPath()}")
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
                        ->view('tables.columns.part-status')
                        ->grow(false)
                        ->sortable()
                        ->label('Status'),
                ])
            ])
            ->filters([
                SelectFilter::make('vote_type')
                    ->options(VoteType::options([VoteType::Certify, VoteType::AdminCertify, VoteType::Hold, VoteType::AdminFastTrack]))
                    ->preload()
                    ->multiple()
                    ->label('My Vote'),
            ])
            ->recordUrl(
                fn (Vote $v): string =>
                    route('parts.show', $v->part)
            )
            ->queryStringIdentifier('userVotes');
    }

}
