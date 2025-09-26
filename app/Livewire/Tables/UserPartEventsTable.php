<?php

namespace App\Livewire\Tables;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Enums\EventType;
use App\Filament\Tables\Columns\EventIconColumn;
use App\Filament\Tables\Columns\PartStatusColumn;
use App\Models\Part\PartEvent;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserPartEventsTable extends BasicTable implements HasActions
{
    use InteractsWithActions;
    public function table(Table $table): Table
    {
        return $table
            ->query(
                PartEvent::with('part', 'part.votes', 'part.official_part')
                    ->unofficial()
                    ->whereHas(
                        'part',
                        fn (Builder $q) =>
                            $q->whereHas(
                                'events',
                                fn (Builder $qu) =>
                                $qu->where('event_type', EventType::Submit)->where('user_id', Auth::user()->id)
                            )
                    )
                    ->where('user_id', '!=', Auth::user()->id)
            )
            ->defaultSort('created_at', 'desc')
            ->heading('Events On My Submits')
            ->columns([
                Split::make([
                    EventIconColumn::make('part_event_type')
                        ->grow(false),
                    TextColumn::make('created_at')
                        ->since()
                        ->sortable()
                        ->label('Date/Time')
                        ->grow(false),
                    ImageColumn::make('image')
                        ->state(
                            function (PartEvent $event) {
                                if (!is_null($event->part)) {
                                    return $event->part->getFirstMediaUrl('image', 'thumb');
                                } else {
                                    // One pixel transparent png
                                    return blank_image_url();
                                }
                            }
                        )
                        ->extraImgAttributes(['class' => 'object-scale-down w-[35px] max-h-[75px]'])
                        ->grow(false),
                    TextColumn::make('user.name')
                        ->description(fn (PartEvent $e): string => $e->user->realname ?? '')
                        ->grow(false)
                        ->visibleFrom('md'),
                    TextColumn::make('part.filename')
                        ->state(
                            fn (PartEvent $e) =>
                                !is_null($e->part) ? $e->part->filename : $e->deleted_filename
                        )
                        ->description(fn (PartEvent $e): string => !is_null($e->part) ? $e->part->description : $e->deleted_description)
                        ->label('Part')
                        ->visibleFrom('md'),
                    Stack::make([
                        TextColumn::make('user.name')
                            ->description(fn (PartEvent $e): string => $e->user->realname ?? '')
                            ->grow(false),
                        TextColumn::make('part.filename')
                            ->state(
                                fn (PartEvent $e) =>
                                    !is_null($e->part) ? $e->part->filename : $e->deleted_filename
                            )
                            ->description(fn (PartEvent $e): string => !is_null($e->part) ? $e->part->description : $e->deleted_description)
                            ->label('Part'),
                    ])->hiddenFrom('sm'),
                    PartStatusColumn::make('status')
                        ->label('Status')
                        ->grow(false),
                ])
            ])
            ->recordUrl(
                fn (PartEvent $e): string =>
                    !is_null($e->part) ? route('parts.show', $e->part) : ''
            )
            ->queryStringIdentifier('userPartEvents');
    }

}
