<?php

namespace App\Livewire\Tables;

use App\Livewire\Tables\BasicTable;
use App\Models\Part\Part;
use App\Models\Part\UnknownPartNumber;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use LaraZeus\Quantity\Components\Quantity;
use Livewire\Attributes\Computed;

class UserUnknownNumbersTable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                auth()->user()->unknown_part_numbers()->getQuery()
            )
            ->heading('uXXXX Numbers Assigned to Me')
            ->columns([
                TextColumn::make('number')
                    ->state(fn (UnknownPartNumber $unk) => "u{$unk->number}")
                    ->sortable(),
                TextColumn::make('in_use')
                    ->state(fn(UnknownPartNumber $unk) => is_null($this->findUPart($unk)) ? 'No' : 'Yes'),
                TextColumn::make('in_use_desciption')
                    ->state(function (UnknownPartNumber $unk) {
                        $p = $this->findUPart($unk);
                        if (!is_null($p)) {
                            return $p->description;
                        }
                        return '';
                    }),
                TextInputColumn::make('notes')
                    ->rules(['required', 'max:255'])
            ])
            ->defaultSort('number', 'asc')
            ->actions([
                Action::make('view')
                    ->url(fn(UnknownPartNumber $unk) => route('parts.list', ['tableSearch' => "u{$unk->number}"]))
                    ->visible(fn(UnknownPartNumber $unk) => !is_null($this->findUPart($unk))),
                DeleteAction::make('delete')
                    ->modalHeading(fn(UnknownPartNumber $unk) => "Remove your reservation of u{$unk->number}?")
                    ->modalDescription('Are you sure you\'d like to remove this number from your reservation list? This cannot be undone.')
                    ->visible(fn(UnknownPartNumber $unk) => is_null($this->findUPart($unk)))
            ])
            ->headerActions([
                Action::make('request')
                    ->label('Request Numbers')
                    ->form([
                        Quantity::make('request_number')
                            ->label('How many numbers are you requesting?')
                            ->default(1)
                            ->maxValue(10)
                            ->minValue(1)
                            ->required()
                    ])
                    ->action(function (array $data) {
                        $parts = Part::where('filename', 'LIKE', 'parts/u9___%.dat')->get();
                        $unk_assigned = UnknownPartNumber::pluck('number')->all();
                        $request_list = [];
                        for ($i = 9000; $i < 10000; $i++) {
                            $part = $parts->first(fn (Part $p) => strpos($p->filename, "parts/u{$i}") !== false);
                            if (!is_null($part) || in_array($i, $unk_assigned)) {
                                continue;
                            }
                            $request_list[] = $i;
                            if (count($request_list) >= $data['request_number']) {
                                break;
                            }
                        }
                        foreach($request_list as $num) {
                            $unk = UnknownPartNumber::create([
                                'number' => $num,
                                'user_id' => auth()->user()->id
                            ]);
                            $unk->save();
                        }
                    })
            ])
            ->paginated(false);
    }

    #[Computed]
    protected function uParts()
    {
        return Part::doesntHave('official_part')->where('filename', 'LIKE', 'parts/u____%.dat')->get();
    }

    protected function findUPart(UnknownPartNumber $unk): ?Part 
    {
        $p = $this->uParts->first(fn (Part $part) => strpos($part->filename, "parts/u{$unk->number}") !== false);
        return $p;
    }
}
