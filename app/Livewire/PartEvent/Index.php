<?php

namespace App\Livewire\PartEvent;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Enums\EventType;
use App\Enums\PartCategory;
use App\Filament\Tables\Columns\EventIconColumn;
use App\Filament\Tables\Columns\PartStatusColumn;
use App\Models\Part\PartEvent;
use App\Filament\Tables\Filters\AuthorFilter;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Attributes\Url;

class Index extends Component implements HasSchemas, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    #[Url]
    public $tableRecordsPerPage = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(PartEvent::query()->with('part', 'part.votes', 'part.official_part'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Split::make([
                    EventIconColumn::make('event_type')
                        ->grow(false),
                    TextColumn::make('created_at')
                        ->since()
                        ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('created_at', $direction)->orderBy('id', 'asc'))
                        ->label('Date/Time')
                        ->grow(false),
                    ImageColumn::make('image')
                        ->state(fn (PartEvent $event) => $event->part?->getFirstMediaUrl('image', 'thumb') ?? blank_image_url())
                        ->extraImgAttributes(['class' => 'object-scale-down w-[35px] max-h-[75px]'])
                        ->grow(false),
                    TextColumn::make('user.realname')
                        ->description(fn (PartEvent $e): string => $e->user->name ?? '')
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
                        TextColumn::make('user.realname')
                            ->description(fn (PartEvent $e): string => $e->user->name ?? '')
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
            ->filters([
                SelectFilter::make('event_type')
                    ->options(EventType::options())
                    ->multiple()
                    ->preload()
                    ->label('Event Type'),
                AuthorFilter::make('user_id'),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_until')
                        ->native(false)
                        ->displayFormat('Y-m-d')
                        ->label('Start Date')
                        ->prefix('From')
                        ->suffix('until now')
                        ->closeOnDateSelection(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Filter::make('part_release_id')
                    ->query(fn (Builder $query): Builder => $query->whereNull('part_release_id'))
                    ->toggle()
                    ->label('Only unofficial part events'),
                Filter::make('sticker_shortcuts')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('part', fn ($q) => $q->where('category', PartCategory::StickerShortcut)))
                    ->toggle()
                    ->label('Hide sticker shortcuts'),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
 //           ->persistFiltersInSession()
 //           ->persistSortInSession()
            ->extremePaginationLinks()
            ->recordUrl(
                fn (PartEvent $e): string =>
                    !is_null($e->part) ? route('parts.show', ['part' => $e->part]) : ''
            )
            ->recordClasses(fn (PartEvent $e) => $e->part?->isOfficial() ? '!bg-green-300' : '');
    }

    public function updatedPaginators($page, $pageName)
    {
        $this->dispatch('page-change');
    }

    #[Layout('components.layout.tracker')]
    public function render(): View
    {
        return view('livewire.part-event.index');
    }
}
