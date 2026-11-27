<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

/**
 * Cases are declared in workflow order, which Part::orderByStatus() sorts by.
 */
enum PartStatus: string
{
    use CanBeOption;

    case Official = 'official';
    case Certified = 'certified';
    case AwaitingAdminReview = 'awaiting_admin_review';
    case Needs1MoreVote = 'needs_1_more_vote';
    case Needs2MoreVotes = 'needs_2_more_votes';
    case UncertifiedSubfiles = 'uncertified_subfiles';
    case ErrorsFound = 'errors_found';

    public function iconColor(): string
    {
        return match ($this) {
            PartStatus::Official, PartStatus::AwaitingAdminReview => 'fill-blue-800',
            PartStatus::Certified => 'fill-lime-400',
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
            PartStatus::Official, PartStatus::AwaitingAdminReview => '#1e40af',
            PartStatus::Certified => '#a3e635',
            PartStatus::Needs1MoreVote, PartStatus::Needs2MoreVotes => '#9ca3af',
            PartStatus::UncertifiedSubfiles => '#fde047',
            PartStatus::ErrorsFound => '#ef4444',
        };
    }

    /** @return array<self> */
    public static function trackerStatus(): array
    {
        return [
            PartStatus::Certified,
            PartStatus::AwaitingAdminReview,
            PartStatus::Needs1MoreVote,
            PartStatus::Needs2MoreVotes,
            PartStatus::ErrorsFound
        ];
    }

    /** @return array<string, string> */
    public static function trackerStatusOptions(): array
    {
        return self::options(self::trackerStatus());
    }

    public function customLabel(): ?string
    {
        return match ($this) {
            PartStatus::Needs1MoreVote => 'Needs 1 More Vote',
            PartStatus::Needs2MoreVotes => 'Needs 2 More Votes',
            default => null,
        };
    }

}
