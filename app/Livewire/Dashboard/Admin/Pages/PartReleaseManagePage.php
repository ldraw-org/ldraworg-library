<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use App\Jobs\GenerateReleaseImage;
use App\Livewire\Dashboard\BasicResourceManagePage;
use App\Models\Part\PartRelease;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PartReleaseManagePage extends BasicResourceManagePage implements HasActions
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    public function mount(): void
    {
        $this->authorize('manage', PartRelease::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PartRelease::query())
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name'),
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('image')
                    ->conversion('thumb')
                    ->grow(false),
                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->sortable(),
                ToggleColumn::make('enabled'),
            ])
            ->recordActions([
                Action::make('set_highlight')
                    ->label('Set Highlights')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->fillForm(fn (PartRelease $release) => [
                        'highlights' => $release
                            ->getMedia('view')
                            ->filter(fn (Media $media) => $media->getCustomProperty('highlight', false))
                            ->pluck('id')
                            ->values()
                            ->all(),
                    ])
                    ->schema([
                        ViewField::make('highlights')
                            ->hiddenLabel()
                            ->view('filament.forms.components.media-image-picker')
                            ->viewData(fn (PartRelease $release) => [
                                'images' => $release
                                    ->getMedia('view')
                                    ->map(fn (Media $media) => [
                                        'id' => $media->id,
                                        'url' => $media->getUrl(),
                                        'name' => $media->getCustomProperty('description'),
                                    ])
                                    ->all(),
                            ]),
                    ])
                    ->action(function (array $data, PartRelease $release) {
                        $selected = array_map('intval', $data['highlights'] ?? []);
                        $changed = false;

                        foreach ($release->getMedia('view') as $media) {
                            $highlight = in_array($media->id, $selected, true);

                            if ($media->getCustomProperty('highlight', false) !== $highlight) {
                                $media->setCustomProperty('highlight', $highlight);
                                $media->save();
                                $changed = true;
                            }
                        }

                        if ($changed) {
                            GenerateReleaseImage::dispatch($release);
                        }
                    })
                    ->visible(fn (PartRelease $release) => !file_exists(public_path("images/updates/{$release->short}.png"))),
            ]);
    }
}
