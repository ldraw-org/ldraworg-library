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
}
