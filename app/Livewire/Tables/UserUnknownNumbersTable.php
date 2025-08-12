<?php

namespace App\Livewire\Tables;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Models\Part\UnknownPartNumber;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use LaraZeus\Quantity\Components\Quantity;

class UserUnknownNumbersTable extends BasicTable implements HasActions
{
    use InteractsWithActions;
    public function table(Table $table): Table
    {
        return $table
            ->query(
                UnknownPartNumber::with('parts')
                    ->where('user_id', Auth::user()->id)
                    ->where(
                        fn (Builder $query) =>
                        $query->orDoesntHave('parts')->orWhereHas(
                            'parts',
                            fn (Builder $query2) =>
                            $query2->unofficial()->doesntHave('official_part')
                        )
                    )
            )
            ->heading('uXXXX Numbers Assigned to Me')
            ->columns([
                TextColumn::make('number')
                    ->state(fn (UnknownPartNumber $unk) => "u{$unk->number}")
                    ->sortable(),
                TextColumn::make('in_use')
                    ->state(fn (UnknownPartNumber $unk) => $unk->parts->isNotEmpty() ? 'Yes' : 'No'),
                TextColumn::make('in_use_desciption')
                    ->state(fn (UnknownPartNumber $unk) => $unk->parts->isNotEmpty() ? $unk->parts->first()->description : ''),
                TextInputColumn::make('notes')
                    ->rules(['required', 'max:255'])
            ])
            ->defaultSort('number', 'asc')
            ->recordActions([
                Action::make('view')
                    ->url(fn (UnknownPartNumber $unk) => route('parts.list', ['tableSearch' => "u{$unk->number}"]))
                    ->visible(fn (UnknownPartNumber $unk) => $unk->parts->isNotEmpty()),
                DeleteAction::make('delete')
                    ->modalHeading(fn (UnknownPartNumber $unk) => "Remove your reservation of u{$unk->number}?")
                    ->modalDescription('Are you sure you\'d like to remove this number from your reservation list? This cannot be undone.')
                    ->visible(fn (UnknownPartNumber $unk) => $unk->parts->isEmpty())
            ])
            ->headerActions([
                Action::make('request')
                    ->label('Request Numbers')
                    ->schema([
                        Quantity::make('request_number')
                            ->label('How many numbers are you requesting?')
                            ->default(1)
                            ->maxValue(10)
                            ->minValue(1)
                            ->required()
                    ])
                    ->action(function (array $data) {
                        $request_list = [];
                        for ($i = 9000; $i < 10000; $i++) {
                            if (!is_null(UnknownPartNumber::firstWhere('number', $i))) {
                                continue;
                            }
                            $request_list[] = $i;
                            if (count($request_list) >= $data['request_number']) {
                                break;
                            }
                        }
                        foreach ($request_list as $num) {
                            $unk = UnknownPartNumber::create([
                                'number' => $num,
                                'user_id' => Auth::user()->id
                            ]);
                            $unk->save();
                        }
                    })
            ])
            ->paginated(false);
    }
}
