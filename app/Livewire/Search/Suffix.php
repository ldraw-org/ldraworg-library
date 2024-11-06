<?php

namespace App\Livewire\Search;

use App\Models\Part;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Collection;
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
    public ?Part $part;
    
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

    public function doSearch()
    {
        $this->form->getState();
        $part = Part::with('patterns', 'composites', 'shortcuts')->where('filename', "parts/{$this->basepart}.dat");
        $this->part = $part->official()->first() ?? $part->first();
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.search.suffix');
    }
}
