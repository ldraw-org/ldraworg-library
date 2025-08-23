<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\CreateAction;
use App\Livewire\Dashboard\BasicResourceManagePage;
use App\Models\Part\Part;
use App\Models\ReviewSummary\ReviewSummary;
use App\Models\ReviewSummary\ReviewSummaryItem;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

class ReviewSummaryManagePage extends BasicResourceManagePage implements HasActions
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public string $title = "Manage Review Summaries";
    protected string $menu = 'admin';

    public function mount(): void
    {
        $this->authorize('manage', ReviewSummary::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ReviewSummary::query())
            ->defaultSort('order')
            ->reorderable('order')
            ->heading('Part Review Summary Management')
            ->columns([
                TextColumn::make('header')
            ])
            ->recordActions([
                EditAction::make()
                    ->schema($this->formSchema()),
                DeleteAction::make()
                    ->before(fn (ReviewSummary $summary) => $summary->items()->delete())
            ])
            ->headerActions([
                CreateAction::make()
                    ->schema($this->formSchema())
            ]);
    }

    protected function formSchema(): array
    {
        return [
            TextInput::make('header')
                ->required()
                ->string(),
            Textarea::make('list')
                ->rows(30)
                ->string()
                ->required()
        ];
    }

}
