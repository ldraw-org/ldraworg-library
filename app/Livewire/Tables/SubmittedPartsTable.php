<?php

namespace App\Livewire\Tables;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;

class SubmittedPartsTable extends BasicTable implements HasActions
{
    use InteractsWithActions;

    #[Reactive]
    public array $parts = [];

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->parts)
            ->columns([
                ImageColumn::make('image')
                    ->grow(false)
                    ->extraImgAttributes(['class' => 'object-scale-down w-[35px] max-h-[75px]']),
                TextColumn::make('filename'),
                TextColumn::make('description'),
            ])
            ->recordUrl(fn (array $record): string => $record['route'])
            ->paginated(false);
    }
}
