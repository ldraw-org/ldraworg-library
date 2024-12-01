<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

enum PartTypeQualifier: string
{
    use CanBeOption;

    case Alias = 'Alias';
    case FlexibleSection = 'Flexible_Section';
    case PhysicalColour = 'Physical_Colour';

}
