<?php

namespace App\Services\Menu\Enums;

use App\Models\Omr\OmrModel;
use App\Services\Menu\Contracts\Navigable;
use Illuminate\Support\Facades\Auth;

enum OtherNavItem implements Navigable
{
    //Downloads
    case UnofficialPartZip;
    case DailySubmitsZip;

    //Omr
    case SetList;
    case AddModel;

    public function label(): string
    {
        return match ($this) {
            self::UnofficialPartZip => 'Download All Unofficial Parts',
            self::DailySubmitsZip => 'Download Last 24 Hours of Submits',
            self::SetList => 'Sets List',
            self::AddModel => 'Add Model',
        };
    }

    public function route(): string
    {
        return match ($this) {
            self::UnofficialPartZip => asset('library/unofficial/ldrawunf.zip'),
            self::DailySubmitsZip => route('tracker.last-day'),
            self::SetList => route('omr.sets.index'),
            self::AddModel => route('omr.add'),
        };
    }

    public function visible(): bool
    {
        return match ($this) {
            self::AddModel => Auth::user()?->can('create', OmrModel::class) ?? false,
            default => true,
        };
    }

}
