<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

enum PartStatus: int
{
    use CanBeOption;

    case Official = 0;
    case Certified = 1;
    case AwaitingAdminReview = 2;
    case Needs1MoreVote = 3;
    case Needs2MoreVotes = 4;
    case UncertifiedSubfiles = 5;
    case ErrorsFound = 6;

    public function iconColor(): string
    {
        return match ($this) {
            PartStatus::Official => 'fill-blue-800',
            PartStatus::Certified => 'fill-lime-400',
            PartStatus::AwaitingAdminReview => 'fill-blue-800',
            PartStatus::Needs1MoreVote => 'fill-gray-400',
            PartStatus::Needs2MoreVotes => 'fill-gray-600',
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
            PartStatus::Needs1MoreVote => '#9ca3af',
            PartStatus::Needs2MoreVotes => '#9ca3af',
            PartStatus::UncertifiedSubfiles => '#fde047',
            PartStatus::ErrorsFound => '#ef4444',
        };
    }

    /** @return array<self> */
    public static function trackerStatus(): array
    {
        return [PartStatus::Certified, PartStatus::AwaitingAdminReview, PartStatus::Needs1MoreVote, PartStatus::Needs2MoreVotes, PartStatus::ErrorsFound];
    }

    /** @return array<string, string> */
    public static function trackerStatusOptions(): array
    {
        return self::options(self::trackerStatus());
    }
}
