<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use App\Models\Part;
use App\Models\ReviewSummary\ReviewSummary;
use App\Models\ReviewSummary\ReviewSummaryItem;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

/**
 * @property Form $form
 */
class ReviewSummaryManagePage extends BasicResourceManagePage
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $title = "Manage Review Summaries";

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
            ->actions([
                EditAction::make()
                    ->form($this->formSchema())
                    ->mutateRecordDataUsing(function (ReviewSummary $summary, array $data): array {
                        $data['manualEntry'] = $summary->toString();
                        return $data;
                    })
                    ->using(fn (ReviewSummary $summary, array $data) => $this->saveEditData($summary, $data)),
                DeleteAction::make()
                    ->before(fn (ReviewSummary $summary) => $summary->items()->delete())

            ])
            ->headerActions([
                CreateAction::make()
                    ->form($this->formSchema())
                    ->using(fn (ReviewSummary $summary, array $data) => $this->saveEditData($summary, $data)),
            ]);
    }

    protected function formSchema(): array
    {
        return [
            TextInput::make('header')
                ->required()
                ->string(),
            Textarea::make('manualEntry')
                ->rows(30)
                ->string()
                ->required()
        ];
    }

    protected function saveEditData(ReviewSummary $summary, array $data)
    {
        if ($summary->exists === false) {
            $summary->header = $data['header'];
            $summary->order = ReviewSummary::nextOrder();
            $summary->save();
        }
        $summary->header = $data['header'];
        $summary->save();
        if (isset($data['manualEntry'])) {
            $summary->items()->delete();
            $lines = explode("\n", $data['manualEntry']);
            $order = 1;
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                if ($line[0] == '/') {
                    $heading = explode(" ", $line, 2)[1] ?? '';
                    ReviewSummaryItem::create([
                        'heading' => empty($heading) ? '' : $heading,
                        'order' => $order + 1,
                        'review_summary_id' => $summary->id
                    ]);
                } else {
                    $part = Part::unofficial()->firstWhere('filename', $line) ?? Part::official()->firstWhere('filename', $line);
                    if (!empty($part)) {
                        ReviewSummaryItem::create([
                            'part_id' => $part->id,
                            'order' => $order + 1,
                            'review_summary_id' => $summary->id
                        ]);
                    }
                }
                $order++;
            }
            $summary->refresh();
        }
        return $summary;
    }
}
