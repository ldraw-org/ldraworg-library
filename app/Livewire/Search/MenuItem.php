<?php

namespace App\Livewire\Search;

use App\Models\Omr\Set;
use App\Models\Part\Part;
use App\Settings\LibrarySettings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;

class MenuItem extends Component
{

    public ?string $search = '';
    public array $results = [];

    #[On('doSearch')]
    public function doSearch()
    {
        $settings = app(LibrarySettings::class);
        $this->results = [];
        if (is_null($this->search) || $this->search == '') {
            return;
        }
        $limit = $settings->quick_search_limit;
        $uparts = Part::select(['id', 'filename', 'description'])->unofficial()->searchHeader($this->search)->orderBy('filename')->take($limit)->get();
        $oparts = Part::select(['id', 'filename', 'description'])->official()->searchHeader($this->search)->orderBy('filename')->take($limit)->get();
        if ($uparts->isNotEmpty()) {
            foreach ($uparts as $part) {
                $this->results['Unofficial Parts'][$part->id] = ['name' => $part->name(), 'description' => $part->description];
            }
        }
        if ($oparts->isNotEmpty()) {
            foreach ($oparts as $part) {
                $this->results['Official Parts'][$part->id] = ['name' => $part->name(), 'description' => $part->description];
            }
        }
        $sets = Set::select(['id', 'name', 'number'])->where(function (Builder $q) {
            $q->orWhereLike('number', "%{$this->search}%")
                ->orWhereLike('name', "%{$this->search}%")
                ->orWhereHas('models', fn (Builder $qu) => $qu->whereLike('alt_model_name', "%{$this->search}%"))
                ->orWhereHas('theme', fn (Builder $qu) => $qu->whereLike('name', "%{$this->search}%"));
        })->orderBy('name')->take($limit)->get();
        if ($sets->isNotEmpty()) {
            foreach ($sets as $set) {
                $this->results['OMR Models'][$set->id] = ['name' => $set->name, 'description' => $set->number];
            }
        }
    }

    public function render()
    {
        return view('livewire.search.menu-item');
    }
}
