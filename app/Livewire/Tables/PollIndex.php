<?php

namespace App\Livewire\Tables;

use App\Models\Poll\Poll;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use LaraZeus\Quantity\Components\Quantity;

/**
 * @property Table $table
 */
class PollIndex extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Poll::query()
                ->when(
                    !Auth::user()->can('manage', Poll::class),
                    fn (Builder $query) => $query
                        ->has('items')
                        ->where('enabled', true)
                        ->where('ends_on', '>=', now())
                        ->whereDoesntHave(
                            'items.votes',
                            fn (Builder $query2) => $query2->where('user_id', Auth::user()->id)
                        )
                )
            )
            ->defaultSort('ends_on')
            ->heading('Current Polls')
            ->headerActions([
                CreateAction::make()
                    ->form($this->pollForm())
                    ->visible(Auth::user()->can('manage', Poll::class))
            ])
            ->actions([
                EditAction::make()
                    ->form($this->pollForm())
                    ->visible(fn (Poll $p) => !$p->enabled && Auth::user()->can('manage', Poll::class)),
                DeleteAction::make()
                    ->visible(fn (Poll $p) => ($p->ends_on < now() || !$p->enabled) && Auth::user()->can('manage', Poll::class)),
                Action::make('view_results')
                    ->visible(fn (Poll $p) => $p->ends_on < now() && Auth::user()->can('manage', Poll::class))
                    ->form([
                        View::make('poll.results')
                    ]),


            ])
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('ends_on'),
                CheckboxColumn::make('enabled')
                    ->visible(Auth::user()->can('manage', Poll::class))
                    ->beforeStateUpdated(function (Poll $p, bool $state) {
                        if ($state && !$p->has_been_enabled) {
                            $p->has_been_enabled = true;
                            $p->save();
                        }
                    }),
            ])
            ->recordUrl(fn (Poll $p): string => route('poll.show', $p));
    }

    protected function pollForm(): array
    {
        return [
            TextInput::make('title')
                ->required(),
            DateTimePicker::make('ends_on')
                ->seconds(false)
                ->native(false)
                ->required(),
            Quantity::make('choices_limit')
                ->default(1)
                ->maxValue(10)
                ->minValue(1)
                ->required()
                ->disabled(fn (Poll $p) => $p->has_been_enabled),
            Repeater::make('items')
                ->disabled(fn (Poll $p) => $p->has_been_enabled)
                ->relationship()
                ->simple(
                    Textarea::make('item')
                        ->required(),
                )
                ->addActionLabel('Add Entry'),
            Checkbox::make('enabled')
        ];
    }
}
