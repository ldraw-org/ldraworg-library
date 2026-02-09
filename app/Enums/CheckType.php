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
            CheckType::Error => __('This part has the following errors'),
            CheckType::Warning => __('This part has the following warnings'),
            CheckType::TrackerHold => __('This part has the following automated holds from the Parts Tracker'),
        };
    }
}
