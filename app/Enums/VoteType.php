<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

enum VoteType: string
{
    use CanBeOption;

    case Comment = 'M';
    case CancelVote = 'N';
    case AdminReview = 'A';
    case Certify = 'C';
    case Hold = 'H';
    case AdminFastTrack = 'T';

    public function iconColor()
    {
        return match ($this) {
            VoteType::Comment => 'fill-blue-500',
            VoteType::CancelVote => 'fill-gray-900',
            VoteType::AdminReview, VoteType::AdminFastTrack => 'fill-purple-500',
            VoteType::Certify => 'fill-green-400',
            VoteType::Hold => 'fill-red-500',
        };

    }

    public function icon(): LibraryIcon
    {
        return match ($this) {
            VoteType::Hold => LibraryIcon::Error,
            VoteType::Comment => LibraryIcon::Comment,
            VoteType::CancelVote => LibraryIcon::CancelVote,
            VoteType::AdminReview => LibraryIcon::AdminReview,
            VoteType::AdminFastTrack => LibraryIcon::AdminFastTrack,
            VoteType::Certify => LibraryIcon::Certify
        };
    }

}
