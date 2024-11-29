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
}
