<?php

namespace App\Filament\Actions;

use App\Filament\Forms\Components\Preview;
use App\LDraw\PartManager;
use App\Models\Part\Part;
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
            ->form([Preview::component()])
            ->mutateRecordDataUsing(function (array $data) use ($part): array {
                $preview = $part->previewValues();
                $data['preview_color'] = $preview['color'];
                $data['preview_x'] = $preview['x'];
                $data['preview_y'] = $preview['y'];
                $data['preview_z'] = $preview['z'];
                $data['preview_rotation'] = $preview['rotation'];
                return $data;
            })
            ->using(function (Part $part, array $data) {
                $preview = null;
                if (Arr::has($data, 'preview_rotation')) {
                    $preview = implode(' ', [
                        Arr::get($data, 'preview_color'),
                        Arr::get($data, 'preview_x'),
                        Arr::get($data, 'preview_y'),
                        Arr::get($data, 'preview_z'),
                        Str::of(Arr::get($data, 'preview_rotation'))->squish()
                    ]);
                    $preview = $preview == '16 0 0 0 1 0 0 0 1 0 0 0 1' ? null : $preview;
                }

                if ($part->preview !== $preview) {
                    $part->preview = $preview;
                    $part->has_minor_edit = true;
                    $part->save();
                    $part->refresh();
                    $part->generateHeader();
                    app(PartManager::class)->updateImage($part);
                }
            })
            ->successNotificationTitle('Header updated')
            ->visible(!$part->isUnofficial() && Auth::user()?->can('updatePreview', $part) ?? false);
    }
}
