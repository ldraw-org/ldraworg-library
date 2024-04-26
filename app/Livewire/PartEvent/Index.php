<?php
namespace App\Livewire\PartEvent;
 
use App\Models\PartEvent;
use App\Tables\Filters\AuthorFilter;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Attributes\Url;

class Index extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;
    
    #[Url]
    public ?array $tableFilters = null;
    #[Url]
    public ?string $tableSortColumn = null;
    #[Url]
    public ?string $tableSortDirection = null;
    #[Url]
    public $tableRecordsPerPage = null;
    
    public function table(Table $table): Table
    {
        return $table
            ->query(PartEvent::query())
            ->defaultSort('created_at', 'desc')
            ->columns([
                Split::make([
                    ViewColumn::make('part_event_type')
                        ->view('components.event.icon.filament-table-icon')
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
                                    return version("images/library/{$event->part->libFolder()}/" . substr($event->part->filename, 0, -4) . '_thumb.png');
                                } else {
                                    return asset('images/library/placeholder.png');
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
                    ViewColumn::make('status')
                        ->view('tables.columns.event-part-status')
                        ->label('Status')
                        ->grow(false),
                ])
            ])
            ->filters([
                SelectFilter::make('part_event_type_id')
                    ->relationship('part_event_type', 'name')
                    ->native(false)
                    ->multiple()
                    ->preload()
                    ->label('Event Type'),
                AuthorFilter::make('user_id'),
                Filter::make('created_at')
                    ->form([
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
                    ->query(fn (Builder $query): Builder => $query->unofficial())
                    ->toggle()
                    ->label('Only unofficial part events'),
            ], layout: FiltersLayout::AboveContent)
                //->persistFiltersInSession()
            ->recordUrl(
                fn (PartEvent $e): string => 
                    !is_null($e->part) ? route($e->part->isUnofficial() ? 'tracker.show' : 'official.show', ['part' => $e->part]) : ''
            )
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->recordClasses(fn (PartEvent $e) => !is_null($e->part) && !$e->part->isUnofficial() ? 'bg-green-300' : '' );
    }
    
    #[Layout('components.layout.tracker')]
    public function render(): View
    {
        return view('livewire.part-event.index');
    }
}