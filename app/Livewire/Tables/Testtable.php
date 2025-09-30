<?php

namespace App\Livewire\Tables;

use App\Enums\PartCategory;
use App\Models\Part\Part;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Testtable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(function (Builder $query) {
                $ids = Part::where('category', PartCategory::StickerShortcut)
                    ->doesntHave('unofficial_part')
                    ->get()
                    ->filter(function (Part $part): bool {
                        foreach (explode("\n", $part->body->body) as $line) {
                            if (Str::startsWith($line, '1 ') && Str::doesntStartWith($line, '1 16')) {
                                return true;
                            }
                        }
                        return false;
                    })
                ->pluck('id')
                ->all();
                return Part::whereIn('id', $ids);
            })
            ->columns(PartTable::columns())
            ->recordUrl(fn (Part $part): string => route('parts.show', $part));
    }
}
