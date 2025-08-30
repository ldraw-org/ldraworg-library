<?php

namespace App\Livewire\Tables;

use App\Enums\PartDependency;
use App\Enums\PartLibrary;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Models\Part\Part;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Illuminate\Support\Str;

#[Lazy]
class PartDependenciesTable extends BasicTable implements HasActions
{
    use InteractsWithActions;
    public PartLibrary $lib;
    public PartDependency $dependency;
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
            ->relationship(fn (): BelongsToMany => $this->part->{$this->dependency->value}()->{$this->lib->value}())
            ->heading(Str::ucfirst($this->lib->value) . " {$this->dependency->value}")
            ->columns(PartTable::columns())
            ->recordActions(PartTable::actions())
            ->recordUrl(
                fn (Part $p): string =>
                    route('parts.show', ['part' => $p])
            )
            ->queryStringIdentifier("{$this->lib->value}-{$this->dependency->value}");
    }
}
