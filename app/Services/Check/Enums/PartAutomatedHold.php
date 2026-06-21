<?php

namespace App\Services\Check\Enums;

use App\Enums\Traits\CanBeOption;
use App\Services\Check\Contracts\CheckItem;
use App\Services\Check\Enums\Traits\HasMessage;
use Filament\Support\Contracts\HasLabel;

enum PartAutomatedHold: string implements CheckItem, HasLabel
{
    use CanBeOption;
    use HasMessage;

    case TrackerNoCertifiedParents = 'tracker_hold.nocertparents';
    case TrackerHasUncertifiedSubfiles = 'tracker_hold.uncertsubs';
    case TrackerHasMissingSubfiles = 'tracker_hold.missing';
    case TrackerAdminHold = 'tracker_hold.adminhold';

    public function type(): CheckType
    {
        return CheckType::TrackerHold;
    }

    public function isMultiLine(): bool
    {
        return $this === self::TrackerHasUncertifiedSubfiles;
    }

    public function multiLineHeader(): ?string
    {
        return $this === self::TrackerHasUncertifiedSubfiles ? 'Has Uncertified Subfiles' : null;
    }

    public function description(): string
    {
        return match ($this) {
            self::TrackerAdminHold => 'On administrative hold',
            self::TrackerNoCertifiedParents => 'No path of certified files to a certified or official parent in the parts folder',
            self::TrackerHasUncertifiedSubfiles => 'Has uncertified subfiles',
            self::TrackerHasMissingSubfiles => 'Has missing subfiles',
        };
    }
}
