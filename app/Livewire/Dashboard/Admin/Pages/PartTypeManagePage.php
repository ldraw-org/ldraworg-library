<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use App\Models\Part\PartType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

class PartTypeManagePage extends BasicResourceManagePage
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $title = "Manage Part Categories";

    public function table(Table $table): Table
    {
        return $table
            ->query(PartType::query())
            ->defaultSort('type')
            ->heading('Part Type Management')
            ->paginated(false)
            ->columns([
                TextColumn::make('type')
                    ->sortable(),
                TextColumn::make('name'),
                TextColumn::make('folder'),
                TextColumn::make('format'),
                TextColumn::make('parts_count')
                    ->counts('parts')
                    ->label('Number of Parts')
                    ->sortable()
            ])
/*
            ->actions([
                EditAction::make()
                    ->form($this->formSchema())
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
            TextInput::make('type')
                ->string()
                ->required(),
            TextInput::make('name')
                ->string()
                ->required(),
            TextInput::make('folder')
                ->string()
                ->required(),
            Select::make('format')
                ->options([
                    'dat' => 'Text File',
                    'png' => 'PNG Image File'
                ])
                ->required()
                ->selectablePlaceholder(false)
        ];
    }
}
