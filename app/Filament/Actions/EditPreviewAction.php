<?php

namespace App\Filament\Actions;

use App\Filament\Forms\Components\PreviewSelect;
use App\Models\Part\Part;
use App\Services\Part\PreviewSync;
use Filament\Actions\EditAction;
use Illuminate\Support\Facades\Auth;

class EditPreviewAction extends EditAction
{
    public function setUp(): void
    {
        parent::setUp();

        $this->label('Edit Preview')
        ->schema([
            PreviewSelect::make()
        ])
        ->mutateRecordDataUsing(function (?Part $record, array $data): array {
            $data['preview_rotation'] = $record->preview->value;
            return $data;
        })
        ->using(function (?Part $record, array $data) {
            app(PreviewSync::class)->updatePartPreview($record, $data['preview_rotation']);
        })
        ->successNotificationTitle('Preview updated')
        ->visible(fn (?Part $record) => $record?->isOfficial() && (Auth::user()?->can('updatePreview', $record) ?? false));
    }
}
