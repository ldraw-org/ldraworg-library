<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

enum PartStatus: string
{
    use CanBeOption;

    case Certified = '1';
    case AwaitingAdmin = '2';
    case NeedsMoreVotes = '4';
    case ErrorsFound = '5';

    public function iconColor(): string
    {
        return match ($this) {
            PartStatus::Certified => 'fill-lime-400',
            PartStatus::AwaitingAdmin => 'fill-blue-700',
            PartStatus::NeedsMoreVotes => 'fill-gray-400',
            PartStatus::ErrorsFound => 'fill-red-500',
        };
    }
}
