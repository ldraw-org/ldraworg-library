<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use App\Models\Part\PartLicense;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

class PartLicenseManagePage extends BasicResourceManagePage
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $title = "Manage Part Licenses";

    public function table(Table $table): Table
    {
        return $table
            ->query(PartLicense::query())
            ->defaultSort('name')
            ->heading('Part License Management')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->sortable(),
                ToggleColumn::make('in_use')
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
            TextInput::make('name')
                ->string()
                ->required(),
            Textarea::make('text')
                ->string()
                ->required(),
            Toggle::make('in_use')
        ];
    }
}
