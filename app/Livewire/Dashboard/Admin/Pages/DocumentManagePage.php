<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use App\Models\Document\Document;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\MarkdownEditor;
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
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table as Table;

class DocumentManagePage extends BasicResourceManagePage
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $title = "Manage Documents";

    public function table(Table $table): Table
    {
        return $table
            ->query(Document::query())
            ->defaultSort('nav_title', 'asc')
            ->heading('Document Management')
            ->columns([
                TextColumn::make('title')
                    ->sortable()
                    ->searchable(),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->formSchema())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['nav_title'] = rawurlencode(str_replace(' ', '-', strtolower($data['title'])));
                        return $data;
                    })
                    ->modalWidth(MaxWidth::Screen),
                DeleteAction::make()
            ])
            ->headerActions([
                CreateAction::make()
                    ->form($this->formSchema())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['nav_title'] = rawurlencode(str_replace(' ', '-', strtolower($data['title'])));
                        return $data;
                    })
                    ->modalWidth(MaxWidth::Screen)
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
                ]),
            Toggle::make('published'),
            Toggle::make('restricted'),
            TextInput::make('maintainer')
                ->string()
                ->required(),
            Textarea::make('revision_history')
                ->string(),
            MarkdownEditor::make('content')
                ->minHeight('30rem')
                ->required()
        ];
    }
}