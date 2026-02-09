<?php

namespace App\Enums;

enum CheckType: string
{
    case Error = 'errors';
    case Warning = 'warnings';
    case TrackerHold = 'tracker_holds';

    /** @return array<self> */
    public static function holdable(): array
    {
        return [CheckType::Error, CheckType::TrackerHold];
    }

    public function statusMessage(): string
    {
        return match ($this) {
            CheckType::Error => 'This part has the following errors',
            CheckType::Warning => 'This part has the following warnings',
            CheckType::TrackerHold => 'This part has the following automated holds from the Parts Tracker',
        };
    }
}
