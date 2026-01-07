<?php

namespace App\Enums;

enum LdrawFileType: string
{
    case TextFile = 'text/plain';
    case Image = 'image/png';
}