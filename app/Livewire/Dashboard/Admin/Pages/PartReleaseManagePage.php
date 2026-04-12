<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use App\Livewire\Dashboard\BasicResourceManagePage;
use App\Models\Part\PartRelease;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

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
                ImageColumn::make('image')
                    ->state(function (PartRelease $release) {
                        $image = "/images/updates/{$release->short}.png";
                        if (file_exists(public_path($image))) {
                            return url($image);
                        }
                        return url("/images/updates/default.png");
                    })
                    ->visibility('public')
                    ->grow(false)
                    ->extraImgAttributes(['class' => 'object-scale-down w-[35px] max-h-[75px]']),
                TextColumn::make('name'),
                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->sortable(),
            ]);
    }

}
