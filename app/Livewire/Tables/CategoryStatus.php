<?php

namespace App\Livewire\Tables;

use App\Enums\PartCategory;
use App\Enums\PartStatus;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Models\Part\Part;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

class CategoryStatus extends BasicTable implements HasActions
{
    use InteractsWithActions;


    public function table(Table $table): Table
    {
        return $table
            ->heading('Categories')
            ->records( fn (?string $sortColumn, ?string $sortDirection): Collection => 
                $this->categoryArray
                    ->when(
                        filled($sortColumn),
                        fn (Collection $data): Collection => $data->sortBy(
                            $sortColumn,
                            SORT_REGULAR,
                            $sortDirection === 'desc',
                        ),
                    )   
            )
            ->columns([
                ImageColumn::make('image')
                    ->grow(false)
                    ->extraImgAttributes(['class' => 'object-scale-down w-[35px] max-h-[75px]']),
                TextColumn::make('category')
                    ->sortable(),
                TextColumn::make('total')
                    ->sortable(),
                TextColumn::make('official')
                    ->sortable(),
                TextColumn::make('unofficial')
                    ->sortable(),
                TextColumn::make('certified')
                    ->sortable(),
                TextColumn::make('admin_ready')
                    ->sortable(),
                TextColumn::make('needs_more_votes')
                    ->sortable(),
                TextColumn::make('held')
                    ->sortable(),
            ])
            ->recordUrl(
                fn (array $record): string => route('parts.list', ['tableFilters' => ['category' => ['values' => [$record['category']]]]])
            )
            ->openRecordUrlInNewTab();
    }

    #[Computed]
    public function categoryArray(): Collection
    {
        $parts = Cache::get('avatar_parts', []);
        return collect(PartCategory::cases())
            ->map( 
                function (PartCategory $cat) use ($parts): array {
                    $status = Part::select('category', 'part_status')
                        ->where('category', $cat)
                        ->doesntHave('unofficial_part')
                        ->get()
                        ->countBy('part_status');
                    $image = Arr::has($parts, $cat->value)
                        ? version('images/library/official/parts/'. str_replace('.dat', '_thumb.png', Arr::get($parts, $cat->value)))
                        : blank_image_url();
                    return [
                        'category' => $cat->value,
                        'image' => $image,
                        'total' => $status->sum(),
                        'official' => $status->get(PartStatus::Official->value, 0),
                        'unofficial' => $status->forget(PartStatus::Official->value)->sum(),
                        'certified' => $status->get(PartStatus::Certified->value, 0),
                        'admin_ready' => $status->get(PartStatus::AwaitingAdminReview->value, 0),
                        'needs_more_votes' => $status->get(PartStatus::NeedsMoreVotes->value, 0),
                        'held' => $status->get(PartStatus::ErrorsFound->value, 0),
                    ];
                } 
            );
    }
}
