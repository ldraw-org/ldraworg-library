<?php

namespace App\Services\Check\Enums;

use App\Enums\Traits\CanBeOption;
use App\Services\Check\Contracts\CheckItem;
use Filament\Support\Contracts\HasLabel;

enum PartAutomatedHold: string implements CheckItem, HasLabel
{
    use CanBeOption;

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
}
