<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

enum PartStatus: int
{
    use CanBeOption;

    case Official = 0;
    case Certified = 1;
    case AwaitingAdminReview = 2;
    case NeedsMoreVotes = 3;
    case UncertifiedSubfiles = 4;
    case ErrorsFound = 5;

    public function iconColor(): string
    {
        return match ($this) {
            PartStatus::Official => 'fill-blue-800',
            PartStatus::Certified => 'fill-lime-400',
            PartStatus::AwaitingAdminReview => 'fill-blue-800',
            PartStatus::NeedsMoreVotes => 'fill-gray-400',
            PartStatus::UncertifiedSubfiles => 'fill-yellow-300',
            PartStatus::ErrorsFound => 'fill-red-500',
        };
    }

    public function icon(): LibraryIcon
    {
        return match ($this) {
            PartStatus::Official => LibraryIcon::Official,
            default => LibraryIcon::UnofficialPartStatus
        };
    }

    public function chartColor(): string
    {
        return match ($this) {
            PartStatus::Official => '#1e40af',
            PartStatus::Certified => '#a3e635',
            PartStatus::AwaitingAdminReview => '#1e40af',
            PartStatus::NeedsMoreVotes => '#9ca3af',
            PartStatus::UncertifiedSubfiles => '#fde047',
            PartStatus::ErrorsFound => '#ef4444',
        };
    }

    public static function trackerStatus(): array
    {
        return [PartStatus::Certified, PartStatus::AwaitingAdminReview, PartStatus::NeedsMoreVotes, PartStatus::ErrorsFound];
    }

    public static function trackerStatusOptions(): array
    {
        return self::options(self::trackerStatus());
    }
}
