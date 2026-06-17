<?php

namespace App\Services\Check\Enums;

enum CheckType: string
{
    case Error = 'errors';
    case Warning = 'warnings';
    case TrackerHold = 'tracker_holds';

    /** @return array<self> */
    public static function holdable(): array
    {
        return [self::Error, self::TrackerHold];
    }

    public function statusMessage(): string
    {
        return match ($this) {
            self::Error => 'This part has the following errors',
            self::Warning => 'This part has the following warnings',
            self::TrackerHold => 'This part has the following automated holds from the Parts Tracker',
        };
    }

    public function statusType(): string
    {
        return match ($this) {
            self::Warning => 'warning',
            self::Error,
            self::TrackerHold => 'error',
        };
    }

    public function enumClass(): string
    {
        return match($this) {
            self::Error       => PartError::class,
            self::Warning     => PartWarning::class,
            self::TrackerHold => PartAutomatedHold::class,
        };
    }

    public static function allCheckItems(): array
    {
        return array_merge(
            PartError::options(),
            PartWarning::options(),
            PartAutomatedHold::options(),
        );
    }

}
