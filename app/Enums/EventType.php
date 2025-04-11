<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

enum EventType: string
{
    use CanBeOption;

    case Review = 'review';
    case Submit = 'submit';
    case HeaderEdit = 'edit';
    case Rename = 'rename';
    case Release = 'release';
    case Delete = 'delete';
    case Comment = 'comment';

    public function iconColor(): string
    {
        return match ($this) {
            EventType::Comment => 'fill-blue-500',
            EventType::Release => 'fill-green-600',
            default => 'fill-black'
        };

    }

    public function icon(): ?LibraryIcon
    {
        return match ($this) {
            EventType::Review => null,
            EventType::Submit => LibraryIcon::File,
            default => LibraryIcon::{$this->name}
        };
    }

}
