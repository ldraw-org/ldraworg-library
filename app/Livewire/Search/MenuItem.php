<?php

namespace App\Livewire\Search;

use App\Models\Omr\Set;
use App\Models\Part\Part;
use App\Settings\LibrarySettings;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class MenuItem extends Component
{
    public ?string $tableSearch = '';

    #[On('doSearch')]
    public function performSearch(): void
    {
        // This just triggers a re-render. 
        // The #[Computed] property will handle the actual logic.
    }

    #[Computed]
    public function results(): array
    {
        if (empty(trim($this->tableSearch))) {
            return [];
        }

        $limit = app(LibrarySettings::class)->quick_search_limit;

        return array_filter([
            'Unofficial Parts' => $this->getPartsSearch(Part::unofficial(), $limit),
            'Official Parts'   => $this->getPartsSearch(Part::official(), $limit),
            'OMR Models'       => $this->getSetsSearch($limit),
        ]);
    }

    private function getPartsSearch(Builder $query, int $limit): array
    {
        return $query->select(['id', 'filename', 'description'])
            ->searchHeader($this->tableSearch)
            ->orderBy('filename')
            ->take($limit)
            ->get()
            ->mapWithKeys(fn ($part) => [
                $part->id => [
                    'name' => $part->meta_name, 
                    'description' => $part->description,
                    'image_url' => $part->getFirstMediaUrl('image','thumb'),
                ]
            ])->toArray();
    }

    private function getSetsSearch(int $limit): array
    {
        return Set::query()
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
            ->get()
            ->mapWithKeys(fn ($set) => [
                $set->id => [
                    'name' => $set->name, 
                    'description' => $set->number,
                    'image_url' => $set->models->first()?->getFirstMediaUrl('image','thumb'),
                ]
            ])->toArray();
    }

    public function render()
    {
        return view('livewire.search.menu-item');
    }
}