<?php

namespace App\Services\Menu\Enums;

use App\Models\Part\Part;
use App\Services\Menu\Contracts\Navigable;
use Illuminate\Support\Facades\Auth;

enum TrackerNavItem implements Navigable
{
    case PartList;
    case PartSearch;
    case PartSummary;
    case StickerSheets;
    case Submit;
    case PartActivity;
    case WeeklyParts;
    case CategoryList;
    case TorsoHelper;
    case ReviewSummaries;
    case NextUpdate;
    case TrackerHistory;

    public function label(): string
    {
        return match ($this) {
            self::PartList => 'Parts List',
            self::PartSearch => 'Part Search',
            self::PartSummary => 'Part Derivations',
            self::CategoryList => 'Part Category List',
            self::StickerSheets => 'Sticker Sheets',
            self::Submit => 'Submit',
            self::PartActivity => 'Activity',
            self::WeeklyParts => 'Parts by Week',
            self::TorsoHelper => 'Torso Shortcut Helper',
            self::ReviewSummaries => 'Review Summaries',
            self::NextUpdate => 'Part in Next Update',
            self::TrackerHistory => 'Tracker History Graph',
        };
    }

    public function route(): string
    {
        return match ($this) {
            self::PartList => route('parts.list'),
            self::PartSearch => route('parts.list'),
            self::PartSummary => route('parts.search.suffix'),
            self::CategoryList => route('parts.category.list'),
            self::StickerSheets => route('parts.sticker-sheet.index'),
            self::Submit => route('tracker.submit'),
            self::PartActivity => route('tracker.activity'),
            self::WeeklyParts => route('tracker.weekly'),
            self::TorsoHelper => route('tracker.torso-helper'),
            self::ReviewSummaries => '',
            self::NextUpdate => route('tracker.next-release'),
            self::TrackerHistory => route('tracker.history'),
        };
    }

    public function visible(): bool
    {
        $user = Auth::user();
        return match ($this) {
            self::Submit => $user?->can(Part::class, 'create') ?? false,
            self::TorsoHelper => $user?->can(Part::class, 'create') ?? false,
            default => true,
        };
    }

}