<?php

namespace App\Enums;

enum CheckType: string
{
    case Error = 'errors';
    case Warning = 'warnings';
    case TrackerHold = 'tracker_holds';

    public static function holdable(): array
    {
        return [CheckType::Error, CheckType::TrackerHold];
    }
}