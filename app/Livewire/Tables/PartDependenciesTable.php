<?php

namespace App\Livewire\Tables;

use App\Models\Part\Part;
use Filament\Tables\Table;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;

#[Lazy]
class PartDependenciesTable extends BasicTable
{
    public bool $official = false;
    public bool $parents = false;
    public Part $part;

    #[On('mass-vote')]
    public function searchUpdated()
    {
        $this->resetTable();
        $this->render();
    }

    public function placeholder(array $params = [])
    {
        return view('livewire.loading', $params);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                if ($this->parents !== false) {
                    $q = $this->official !== false ? $this->part->parents()->whereNotNull('part_release_id') : $this->part->parents()->whereNull('part_release_id');
                } else {
                    $q = $this->official !== false ? $this->part->subparts()->whereNotNull('part_release_id') : $this->part->subparts()->whereNull('part_release_id');
                }
                return $q;
            })
            ->heading(($this->official ? "Official" : "Unofficial") . ($this->parents ? " parent parts" : " subparts"))
            ->emptyState(view('tables.empty', ['none' => 'None']))
            ->columns(PartTable::columns())
            ->actions(PartTable::actions())
            ->recordUrl(
                fn (Part $p): string =>
                    route('parts.show', ['part' => $p])
            )
            ->queryStringIdentifier(($this->official ? "official" : "unofficial") . ($this->parents ? "Parents" : "Subparts"));
    }
}
