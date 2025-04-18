<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use App\Livewire\Dashboard\BasicResourceManagePage;
use App\Models\Document\Document;
use App\Models\Document\DocumentCategory;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table as Table;

class DocumentManagePage extends BasicResourceManagePage
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $title = "Manage Documents";
    protected string $menu = 'admin';

    public function mount(): void
    {
        $this->authorize('manage', Document::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Document::query())
            ->defaultSort('order')
            ->reorderable('order')
            ->heading('Document Management')
            ->columns([
                TextColumn::make('title')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('category.category')
                    ->sortable()
                    ->searchable(),
                ToggleColumn::make('published'),
                ToggleColumn::make('restricted')
            ])
            ->actions([
                EditAction::make()
                    ->form($this->formSchema())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['nav_title'] = rawurlencode(str_replace(' ', '-', strtolower($data['title'])));
                        return $data;
                    })
                    ->modalWidth(MaxWidth::SevenExtraLarge),
                DeleteAction::make()
            ])
            ->headerActions([
                CreateAction::make()
                    ->form($this->formSchema())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['nav_title'] = rawurlencode(str_replace(' ', '-', strtolower($data['title'])));
                        $data['order'] = Document::nextOrder();
                        return $data;
                    })
                    ->modalWidth(MaxWidth::SevenExtraLarge)
            ]);
    }

    protected function formSchema(): array
    {
        return [
            TextInput::make('title')
                ->string()
                ->required(),
            Select::make('document_category_id')
                ->relationship(name: 'category', titleAttribute: 'category')
                ->createOptionForm([
                    TextInput::make('category')
                        ->required(),
                ])
                ->createOptionUsing(function (array $data): int {
                    $data['order'] = DocumentCategory::nextOrder();
                    return DocumentCategory::create($data)->getKey();
                }),
            Section::make([
                Toggle::make('published'),
                Toggle::make('restricted'),
            ])->columns(2),
            TextInput::make('maintainer')
                ->string()
                ->required(),
            Textarea::make('revision_history')
                ->string(),
            Textarea::make('content')
                ->required()
        ];
    }
}
