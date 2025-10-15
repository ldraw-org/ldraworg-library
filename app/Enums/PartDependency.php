<?php

namespace App\Enums;

enum PartDependency: string
{
    case Parents = 'parents';
    case Subparts = 'subparts';
}
