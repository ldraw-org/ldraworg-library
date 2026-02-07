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
        return [CheckType::Error, CheckType::TrackerHold];
    }
}
