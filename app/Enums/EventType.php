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

    public function icon(): ?string
    {
        return match ($this) {
            EventType::Review => null,
            EventType::Comment => 'fas-comment',
            EventType::Submit => 'fas-file',
            EventType::HeaderEdit => 'fas-edit',
            EventType::Rename => 'fas-file-export',
            EventType::Delete => 'fas-recycle',
            EventType::Release => 'fas-graduation-cap',
        };
    }

}
