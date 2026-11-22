<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\CreateAction;
use App\Livewire\Dashboard\BasicResourceManagePage;
use App\Models\ReviewSummary;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReviewSummaryManagePage extends BasicResourceManagePage
{
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
            ->columns([
                TextColumn::make('header')
            ])
            ->recordActions([
                EditAction::make()
                    ->schema($this->formSchema()),
                DeleteAction::make()
            ])
            ->headerActions([
                CreateAction::make()
                    ->schema($this->formSchema())
                    ->mutateDataUsing(function (array $data): array {
                        $data['order'] = ReviewSummary::nextOrder();
                        return $data;
                    })
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
