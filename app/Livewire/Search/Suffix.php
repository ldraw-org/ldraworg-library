<?php

namespace App\Livewire\Search;

use Filament\Schemas\Schema;
use App\Models\Part\Part;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property \Filament\Schemas\Schema $form
 * @property Collection $baseparts
 */
class Suffix extends Component implements HasSchemas
{
    use InteractsWithSchemas;

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('basepart')
                    ->label('Base Part Number')
                    ->required()
                    ->string(),
            ]);
    }

    public function doSearch()
    {
        $this->form->getState();
        $part = Part::with('patterns', 'composites', 'shortcuts')->where('filename', "parts/{$this->basepart}.dat")->official()->first();
        if (is_null($part)) {
            $part = Part::with('patterns', 'composites', 'shortcuts')->where('filename', "parts/{$this->basepart}.dat")->first();
        }
        $this->part = $part;
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.search.suffix');
    }
}
