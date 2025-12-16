<?php

namespace App\Livewire\Search;

use App\Models\Omr\Set;
use App\Models\Part\Part;
use App\Settings\LibrarySettings;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;

class MenuItem extends Component
{
    public ?string $tableSearch = '';
    public array $results = [];

    #[On('doSearch')]
    public function doSearch()
    {
        $settings = app(LibrarySettings::class);
        $this->results = [];
        if (is_null($this->tableSearch) || $this->tableSearch == '') {
            return;
        }
        $limit = $settings->quick_search_limit;
        $uparts = Part::select(['id', 'filename', 'description'])->unofficial()->searchHeader($this->tableSearch)->orderBy('filename')->take($limit)->get();
        $oparts = Part::select(['id', 'filename', 'description'])->official()->searchHeader($this->tableSearch)->orderBy('filename')->take($limit)->get();
        if ($uparts->isNotEmpty()) {
            foreach ($uparts as $part) {
                $this->results['Unofficial Parts'][$part->id] = ['name' => $part->meta_name, 'description' => $part->description];
            }
        }
        if ($oparts->isNotEmpty()) {
            foreach ($oparts as $part) {
                $this->results['Official Parts'][$part->id] = ['name' => $part->meta_name, 'description' => $part->description];
            }
        }
        $sets = Set::query()
            ->select(['sets.id', 'sets.name', 'sets.number'])
            ->has('models')
            ->where(function (Builder $q) {
                $search = "%{$this->tableSearch}%";

                $q->orWhereLike('sets.number', $search)
                ->orWhereLike('sets.name', $search)
                ->orWhereRelation('models', 'alt_model_name', 'LIKE', $search)
                ->orWhereRelation('theme', 'name', 'LIKE', $search);
            })
            ->orderBy('sets.name')
            ->take($limit)
            ->get();

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
