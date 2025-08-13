<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\CreateAction;
use App\Livewire\Dashboard\BasicResourceManagePage;
use App\Models\Document\DocumentCategory;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table as Table;

class DocumentCategoryManagePage extends BasicResourceManagePage implements HasActions
{
    use InteractsWithActions;
    use InteractsWithSchemas;
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
            ->recordActions([
                EditAction::make()
                    ->schema($this->formSchema()),
                DeleteAction::make()
                    ->visible(fn (DocumentCategory $c) => $c->documents->isEmpty())
            ])
            ->headerActions([
                CreateAction::make()
                    ->schema($this->formSchema())
                    ->mutateDataUsing(function (array $data): array {
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
