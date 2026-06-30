<?php

namespace App\Services\Support\Enums;

enum ReleaseType: string
{
    case Update   = 'UPDATE';
    case Complete = 'COMPLETE';
    case Base     = 'BASE';
}
