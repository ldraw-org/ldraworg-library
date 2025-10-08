<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Actions\DeleteAction;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Section;
use App\Livewire\Dashboard\BasicResourceManagePage;
use App\Models\Document\Document;
use App\Models\Document\DocumentCategory;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table as Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DocumentManagePage extends BasicResourceManagePage implements HasActions
{
    use InteractsWithActions;
    use InteractsWithSchemas;
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
            ->defaultGroup(
                Group::make('category.title')
                    ->orderQueryUsing(fn (Builder $query, string $direction) => $query->orderBy('order', 'desc'))
                    ->collapsible()
            )
            ->reorderable('order')
            ->heading('Document Management')
            ->columns([
                TextColumn::make('title')
                    ->sortable()
                    ->searchable(),
                ToggleColumn::make('published'),
                ToggleColumn::make('restricted')
            ])
            ->paginated(false)
            ->recordActions([
                Action::make('view')
                    ->url(fn (Document $d) => route('documentation.show', [$d->category, $d]))
                    ->openUrlInNewTab(),
                EditAction::make()
                    ->schema($this->formSchema())
                    ->mutateDataUsing(function (array $data): array {
                        $data['slug'] = Str::slug($data['title']);
                        return $data;
                    })
                    ->modalWidth(Width::SevenExtraLarge),
                DeleteAction::make()
            ])
            ->headerActions([
                CreateAction::make()
                    ->schema($this->formSchema())
                    ->mutateDataUsing(function (array $data): array {
                        $data['slug'] = Str::slug($data['title']);
                        $data['order'] = Document::nextOrder();
                        return $data;
                    })
                    ->modalWidth(Width::SevenExtraLarge)
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
                    TextInput::make('title')
                        ->required(),
                ])
                ->createOptionUsing(function (array $data): int {
                    $data['slug'] = Str::slug($data['title']);
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
            MarkdownEditor::make('revision_history')
                ->string(),
            MarkdownEditor::make('content')
                ->required()
        ];
    }
}
