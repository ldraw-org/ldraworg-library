<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use App\Models\Part\PartCategory;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

class PartCategoryManagePage extends BasicResourceManagePage
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $title = "Manage Part Categories";

    public function table(Table $table): Table
    {
        return $table
            ->query(PartCategory::query())
            ->defaultSort('category')
            ->heading('Part Category Management')
            ->paginated(false)
            ->columns([
                TextColumn::make('category')
                    ->sortable(),
                TextColumn::make('parts_count')
                    ->counts('parts')
                    ->label('Number of Parts')
                    ->sortable()
            ])
/*
            ->actions([
                EditAction::make()
                    ->form($this->formSchema()),
            ])
*/
            ->headerActions([
                CreateAction::make()
                    ->form($this->formSchema())
            ]);
    }

    protected function formSchema(): array
    {
        return [
            TextInput::make('category')
                ->string()
                ->required(),
        ];
    }
}
