<?php

namespace App\Livewire\Tables;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Enums\CheckType;
use App\Enums\PartError;
use App\Filament\Actions\Part\Download\PartFileDownloadAction;
use App\Models\Part\Part;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OfficialPartsWithErrorsTable extends BasicTable implements HasActions
{
    use InteractsWithActions;
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::official()->hasErrors()->whereDoesntHave('unofficial_part')
            )
            ->heading('Official Parts With Errors')
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('image')
                    ->conversion('thumb')
                    ->grow(false)
                    ->extraImgAttributes(['class' => 'object-scale-down w-[35px] max-h-[75px]']),
                TextColumn::make('filename')
                    ->sortable(),
                TextColumn::make('description')
                    ->sortable(),
                TextColumn::make('errors')
                    ->state(fn (Part $part) => $part->errors->map->message())
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->wrap()
            ])
            ->recordActions([
                PartFileDownloadAction::make()
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
