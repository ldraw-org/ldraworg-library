<?php

namespace App\Livewire\Search;

use App\Models\Part\Part;
use App\Models\User;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Livewire\Attributes\Url;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;

/**
 * @property Form $form
 */
class Parts extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    #[Url(as: 's')]
    public ?string $search = '';
    #[Url(except: 'header')]
    public ?string $scope = 'header';
    #[Url]
    public ?int $user_id = null;
    #[Url]
    public ?bool $exclude_user = false;
    #[Url]
    public ?bool $include_history = false;
    #[Url]
    public ?string $status = '';
    #[Url]
    public ?array $part_type_id = [];


    public function mount(): void
    {
        $this->data = [
            'search' => $this->search,
            'scope' => $this->scope,
            'user_id' => $this->user_id,
            'exclude_user' => $this->exclude_user,
            'include_history' => $this->include_history,
            'status' => $this->status,
            'part_type_id' => $this->part_type_id,
        ];
        $this->form->fill($this->data);
        //$this->doSearch();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Search Parameters')
                    ->schema([
                        TextInput::make('search')
                            ->nullable()
                            ->string(),
                        Select::make('scope')
                            ->options([
                                'filename' => 'Filename only',
                                'description' => 'Filename and description',
                                'header' => 'File header',
                                'file' => 'Entire file (very slow)'
                            ])
                            ->default('header')
                            ->selectablePlaceholder(false)
                            ->native(false),
                    ]),
                Fieldset::make('Filters')
                    ->columns(3)
                    ->schema([
                        Section::make([
                            Select::make('user_id')
                                ->relationship(
                                    name: 'user',
                                    modifyQueryUsing: fn (Builder $query) => $query->orderBy('realname', 'asc')
                                )
                                ->getOptionLabelFromRecordUsing(fn (User $u) => "{$u->realname} [{$u->name}]")
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->nullable(),
                            Split::make([
                                Toggle::make('exclude_user'),
                                Toggle::make('include_history'),
                            ]),
                        ])
                            ->columnSpan(1),
                        Select::make('status')
                            ->options([
                                'certified' => 'Certified',
                                'adminreview' => 'Needs Admin Review',
                                'memberreview' => 'Needs More Votes',
                                'held' => 'Hold',
                            ])
                            ->native(false)
                            ->nullable(),
                        Select::make('part_type_id')
                            ->relationship(name: 'type', titleAttribute: 'name')
                            ->multiple()
                            ->preload()
                            ->native(false)
                            ->nullable(),
                    ]),
            ])
            ->model(Part::class);
    }

    public function doSearch(): void
    {
        $this->form->getState();
        $this->data = [
            'search' => $this->search,
            'scope' => $this->scope,
            'user_id' => $this->user_id,
            'exclude_user' => $this->exclude_user,
            'include_history' => $this->include_history,
            'status' => $this->status,
            'part_type_id' => $this->part_type_id,
        ];
        $this->dispatch('search-updated');
    }

    #[Layout('components.layout.tracker')]
    public function render(): View
    {
        return view('livewire.search.parts');
    }
}
