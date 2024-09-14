<?php

namespace App\Livewire\Tables;

use App\Models\Part;
use App\Filament\Part\Tables\PartTable;
use Filament\Tables\Table;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\On;

class SearchParts extends BasicTable
{
    #[Modelable]
    public array $data;
    public $unofficial;

    #[On('search-updated')]
    public function searchUpdated()
    {
        $this->resetTable();
        $this->render();
    }

    public function table(Table $table): Table
    {
        return $table
        ->query(
            Part::searchPart($this->data['search'] ?? '', in_array($this->data['scope'] ?? '', ['filename', 'description', 'header', 'file']) ? $this->data['scope'] : 'header')
                ->when(
                    $this->unofficial === true,
                    fn ($q) => $q->unofficial(),
                    fn ($q) => $q->official()
                )
                ->when(
                    is_numeric($this->data['user_id'] ?? ''),
                    function ($q) {
                        $opr = ($this->data['exclude_user'] ?? false) ? '!=' : '=';
                        if ($this->data['include_history'] ?? false) {
                            $q->where(function ($q) use ($opr) {
                                $q->orWhere('user_id', $opr, $this->data['user_id'])->orWhereHas('history', function ($qu) use ($opr) {
                                    $qu->where('user_id', $opr, $this->data['user_id']);
                                });
                            });
                        } else {
                            $q->where('user_id', $opr, $this->data['user_id']);
                        }
                    }
                )
                ->when(
                    $this->unofficial && ($this->data['status'] ?? '') != '',
                    fn ($q) => $q->partStatus($this->data['status'])
                )
                ->when(
                    count($this->data['part_type_id'] ?? []) > 0,
                    fn ($q) => $q->whereIn('part_type_id', $this->data['part_type_id'])
                )
        )
        ->heading($this->unofficial ? 'Unofficial Part Results' : 'Official Part Results')
        ->defaultSort('filename', 'asc')
        ->emptyState(view('tables.empty', ['none' => 'None']))
        ->columns(PartTable::columns())
        ->actions(PartTable::actions())
        ->recordUrl(
            fn (Part $p): string =>
                route($p->isUnofficial() ? 'tracker.show' : 'official.show', ['part' => $p])
        )
        ->queryStringIdentifier($this->unofficial === true ? 'unofficialPartSearch' : 'officialPartSearch')
        ->striped();
    }
}
