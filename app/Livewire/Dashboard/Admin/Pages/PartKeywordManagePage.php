<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Livewire\Dashboard\BasicResourceManagePage;
use App\Jobs\MassHeaderGenerate;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

class PartKeywordManagePage extends BasicResourceManagePage implements HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public string $title = "Manage Part Keywords";
    protected string $menu = 'admin';

    public function mount(): void
    {
        $this->authorize('manage', PartKeyword::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PartKeyword::query())
            ->defaultSort('keyword')
            ->heading('Part Keyword Management')
            ->columns([
                TextColumn::make('keyword')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('parts_count')
                    ->counts('parts')
                    ->label('Number of Parts')
                    ->sortable()
            ])
            ->recordActions([
                EditAction::make()
                    ->schema($this->editFormSchema())
                    ->using(fn (PartKeyword $keyword, array $data) => $this->editKeyword($keyword, $data)),
                EditAction::make('merge')
                    ->label('Merge')
                    ->schema($this->mergeFormSchema())
                    ->using(fn (PartKeyword $keyword, array $data) => $this->mergeKeyword($keyword, $data))
                    ->hidden(fn (PartKeyword $keyword) => $keyword->parts->count() < 1),
                DeleteAction::make()
                    ->hidden(fn (PartKeyword $keyword) => $keyword->parts->isNotEmpty()),
            ]);
    }

    protected function editFormSchema(): array
    {
        return [
            TextInput::make('keyword')
                ->string()
                ->required(),
        ];
    }

    protected function editKeyword(PartKeyword $keyword, array $data)
    {
        if ($keyword->keyword != trim($data['keyword'])) {
            $keyword->keyword = trim($data['keyword']);
            $keyword->save();
            $keyword->refresh();
            // The DB is case insensitive and diacritic neutral
            // Handle the case when we want to change the case of things
            if ($keyword->keyword != trim($data['keyword'])) {
                $keyword->keyword = '';
                $keyword->save();
                $keyword->keyword = trim($data['keyword']);
                $keyword->save();
            }
            $keyword->parts()->official()->update(['has_minor_edit' => true]);
            MassHeaderGenerate::dispatch($keyword->parts);
        }
    }

    protected function mergeFormSchema(): array
    {
        return [
            TextInput::make('keyword')
                ->string()
                ->required()
                ->readOnly(),
            Select::make('merge-keyword')
                ->label('Keyword to merge in')
                ->options(PartKeyword::pluck('keyword', 'id'))
                ->searchable()
                ->exists(table: PartKeyword::class, column: 'id')
                ->required()
        ];
    }

    protected function mergeKeyword(PartKeyword $keyword, array $data)
    {
        $finalKeyword = PartKeyword::find($data['merge-keyword']);
        $finalKeyword->parts()->official()->update(['has_minor_edit' => true]);
        $finalKeyword->parts->each(fn (Part $p) => $p->keywords()->toggle([$keyword->id, $finalKeyword->id]));
        MassHeaderGenerate::dispatch($finalKeyword->parts);
        $finalKeyword->delete();
    }

}
