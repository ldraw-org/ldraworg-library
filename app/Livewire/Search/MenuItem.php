<?php

namespace App\Livewire\Search;

use App\Models\Omr\Set;
use App\Models\Part\Part;
use App\Settings\LibrarySettings;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class MenuItem extends Component
{
    public string $search;
    public array $results = [];
    public bool $hasResults = false;

    public function doSearch(LibrarySettings $settings)
    {
        $this->results = [];
        $this->hasResults = false;
        if (empty($this->search) || !is_string($this->search)) {
            return;
        }
        $limit = $settings->quick_search_limit;
        $uparts = Part::select(['id', 'filename', 'description'])->unofficial()->searchHeader($this->search)->orderBy('filename')->take($limit)->get();
        $oparts = Part::select(['id', 'filename', 'description'])->official()->searchHeader($this->search)->orderBy('filename')->take($limit)->get();
        if ($uparts->count() > 0) {
            $this->hasResults = true;
            foreach ($uparts as $part) {
                $this->results['Unofficial Parts'][$part->id] = ['name' => $part->name(), 'description' => $part->description];
            }
        }
        if ($oparts->count() > 0) {
            $this->hasResults = true;
            foreach ($oparts as $part) {
                $this->results['Official Parts'][$part->id] = ['name' => $part->name(), 'description' => $part->description];
            }
        }
        $sets = Set::select(['id', 'name', 'number'])->where(function (Builder $q) {
            $q->orWhere('number', 'LIKE', "%{$this->search}%")
                ->orWhere('name', 'LIKE', "%{$this->search}%")
                ->orWhereHas('models', fn (Builder $qu) => $qu->where('alt_model_name', 'LIKE', "%{$this->search}%"))
                ->orWhereHas('theme', fn (Builder $qu) => $qu->where('theme', 'LIKE', "%{$this->search}%"));
        })->orderBy('name')->take($limit)->get();
        if ($sets->count() > 0) {
            $this->hasResults = true;
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
