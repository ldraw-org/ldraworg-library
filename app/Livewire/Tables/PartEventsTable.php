<?php

namespace App\Livewire\Tables;

use App\Enums\EventType;
use App\Filament\Tables\Columns\EventIconColumn;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Models\Part\Part;
use App\Models\Part\PartEvent;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Illuminate\Support\Str;

#[Lazy]
class PartEventsTable extends BasicTable implements HasActions
{
    use InteractsWithActions;
    public Part $part;

    public function placeholder(array $params = [])
    {
        return view('livewire.loading', $params);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): HasMany => $this->part->events()->official())
            ->defaultSort('created_at', 'desc')
            ->groups([
                Group::make('part_release_id')
                    ->label('Update')
                    ->getTitleFromRecordUsing(fn (PartEvent $event) => $event->release?->name ?? 'Unofficial')
                    ->collapsible()
            ])
            ->defaultGroup('part_release_id')
            ->columns([
                Stack::make([
                    Split::make([
                        EventIconColumn::make('event_type')
                            ->label('event')
                            ->grow(false),
                        TextColumn::make('created_at')
                            ->since()
                            ->grow(false),
                        TextColumn::make('user.realname')
                            ->description(fn (PartEvent $event) => $event->user->name)
                            ->grow(false),
                    ]),
                    TextColumn::make('comment')
                        ->state(fn (PartEvent $event) => match($event->event_type) {
                            EventType::Rename => "{$event->moved_from_filename} to {$event->moved_to_filename}",
                            EventType::HeaderEdit => "Header Edited",
                            EventType::Review => is_null($event->vote_type) ? 'Vote cancelled' : "Voted {$event->vote_type->label()}",
                            default => $event->processedComment(),
                        })
                        ->html()
                        ->fontFamily(FontFamily::Mono)
                        ->extraAttributes(['class' => 'event-comment break-words'])
                        ->wrap(),
                ])   
            ])
            ->queryStringIdentifier("eventHistory");
    }
}
