<?php

namespace App\Livewire\Tables;

use App\Enums\PartError;
use App\LDraw\Check\ErrorCheckBag;
use App\Models\Part\Part;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class OfficialPartsWithErrorsTable extends BasicTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::whereJsonLength('part_check_messages->errors', '>', 0)->official()->whereDoesntHave('unofficial_part')
            )
            ->heading('Official Parts With Errors')
            ->columns([
                ImageColumn::make('image')
                    ->state(
                        fn (Part $p): string => asset("images/library/{$p->libFolder()}/" . substr($p->filename, 0, -4) . '_thumb.png')
                    )
                    ->grow(false)
                    ->extraImgAttributes(['class' => 'object-scale-down w-[35px] max-h-[75px]']),
                TextColumn::make('filename')
                    ->sortable(),
                TextColumn::make('description')
                    ->sortable(),
                TextColumn::make('part_check_messages')
                    ->state(fn (Part $part) => implode(", ", ErrorCheckBag::errorsFromArray($part->errors)))
                    ->wrap()
            ])
            ->actions([
                Action::make('download')
                ->url(fn (Part $part) => route($part->isUnofficial() ? 'unofficial.download' : 'official.download', $part->filename))
                ->button()
                ->outlined()
                ->color('info'),
            ])
            ->filters([
                SelectFilter::make('errors')
                    ->label('Part Errors')
                    ->options(PartError::options())
                    ->multiple()
                    ->query(function (Builder $query, array $data) {
                        if (count($data['values']) == 1) {
                            $query->hasError($data['values'][0]);
                        } else {
                            $query->where(function (Builder $query_inner) use ($data) {
                                foreach ($data['values'] as $value) {
                                    $query_inner->orHasError($value);
                                }
                            });
                        }
                   })
            ])
            ->recordUrl(fn (Part $p): string => route('parts.show', $p))
            ->queryStringIdentifier('officialErrors');
    }
}
