<?php

namespace App\Services\Menu\Enums;

use App\Services\Menu\Contracts\Navigable;

enum OtherNavItem implements Navigable
{
    //Downloads
    case UnofficialPartZip;
    case DailySubmitsZip;

    //Omr
    case SetList;

    public function label(): string
    {
        return match ($this) {
            self::UnofficialPartZip => 'Download All Unofficial Parts',
            self::DailySubmitsZip => 'Download Last 24 of Submits',
            self::SetList => 'Sets List',
        };
    }

    public function route(): string
    {
        return match ($this) {
            self::UnofficialPartZip => asset('library/unofficial/ldrawunf.zip'),
            self::DailySubmitsZip => route('tracker.last-day'),
            self::SetList => route('omr.sets.index'),
        };
    }

    public function visible(): bool
    {
        return true;
    }

}