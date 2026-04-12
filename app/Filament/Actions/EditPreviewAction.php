<?php

namespace App\Filament\Actions;

use App\Filament\Forms\Components\PreviewSelect;
use App\Services\LDraw\Managers\Part\PartManager;
use App\Models\Part\Part;
use App\Services\Part\PartPreviewService;
use Filament\Actions\EditAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EditPreviewAction
{
    public static function make(Part $part, ?string $name = null): EditAction
    {
        return EditAction::make($name)
            ->label('Edit Preview')
            ->record($part)
            ->schema([
                PreviewSelect::make()
            ])
            ->mutateRecordDataUsing(function (array $data) use ($part): array {
                $data['preview_rotation'] = $part->preview->value;
                return $data;
            })
            ->using(function (Part $part, array $data) {
                app(PartPreviewService::class)->updatePartPreview($part, $data['preview_rotation']);
            })
            ->successNotificationTitle('Header updated')
            ->visible(!$part->isUnofficial() && (Auth::user()?->can('updatePreview', $part) ?? false));
    }
}
