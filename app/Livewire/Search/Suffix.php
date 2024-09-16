<?php

namespace App\Livewire\Search;

use App\Models\Part;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property Form $form
 * @property Collection $baseparts
 */
class Suffix extends Component implements HasForms
{
    use InteractsWithForms;

    public string $activeTab = 'patterns';

    #[Url]
    public ?string $basepart = null;

    public function mount(): void
    {
        $this->form->fill(['basepart' => $this->basepart]);
        if (!is_null($this->basepart)) {
            $this->doSearch();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('basepart')
                    ->label('Base Part Number')
                    ->required()
                    ->string(),
            ]);
    }

    #[Computed(persist: true)]
    public function baseparts()
    {
        if (empty($this->basepart)) {
            return new Collection();
        }
        return Part::with(['votes', 'official_part'])
            ->doesntHave('unofficial_part')
            ->whereRelation('type', 'folder', 'parts/')
            ->where('filename', 'LIKE', "parts/{$this->basepart}%.dat")
            ->orderBy('filename', 'asc')
            ->get();
    }

    #[Computed]
    public function patterns()
    {
        return $this->baseparts->patterns($this->basepart);
    }

    #[Computed]
    public function composites()
    {
        return $this->baseparts->composites($this->basepart);
    }

    #[Computed]
    public function shortcuts()
    {
        return $this->baseparts->sticker_shortcuts($this->basepart);
    }

    public function doSearch()
    {
        $this->form->getState();
        unset($this->patterns);
        unset($this->composites);
        unset($this->baseparts);
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.search.suffix');
    }
}
