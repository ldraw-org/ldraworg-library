<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

enum VoteType: string
{
    use CanBeOption;

    case Comment = 'M';
    case CancelVote = 'N';
    case AdminCertify = 'A';
    case Certify = 'C';
    case Hold = 'H';
    case AdminFastTrack = 'T';

    public function iconColor()
    {
        return match ($this) {
            VoteType::Comment => 'fill-blue-500',
            VoteType::CancelVote => 'fill-gray-900',
            VoteType::AdminCertify, VoteType::AdminFastTrack => 'fill-lime-500',
            VoteType::Certify => 'fill-green-400',
            VoteType::Hold => 'fill-red-500',
        };

    }

    public function icon()
    {
        return match ($this) {
            VoteType::Comment => 'fas-comment',
            VoteType::CancelVote => 'fas-undo',
            VoteType::AdminCertify, VoteType::AdminFastTrack, VoteType::Certify => 'fas-check',
            VoteType::Hold => 'fas-circle-exclamation',
        };
    }

}
