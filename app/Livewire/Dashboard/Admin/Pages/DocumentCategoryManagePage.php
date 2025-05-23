<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use App\Livewire\Dashboard\BasicResourceManagePage;
use App\Models\Document\DocumentCategory;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table as Table;

class DocumentCategoryManagePage extends BasicResourceManagePage
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $title = "Manage Document Categories";
    protected string $menu = 'admin';

    public function mount(): void
    {
        $this->authorize('manage', DocumentCategory::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DocumentCategory::query())
            ->defaultSort('order')
            ->reorderable('order')
            ->heading('Document Cateogory Management')
            ->columns([
                TextColumn::make('category')
                    ->sortable()
                    ->searchable(),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->formSchema()),
                DeleteAction::make()
                    ->visible(fn (DocumentCategory $c) => $c->documents->isEmpty())
            ])
            ->headerActions([
                CreateAction::make()
                    ->form($this->formSchema())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['order'] = DocumentCategory::nextOrder();
                        return $data;
                    })
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
