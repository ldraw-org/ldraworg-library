<?php

namespace App\View\Components;

use App\Enums\LibraryIcon as EnumsLibraryIcon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;


class LibraryIcon extends Component
{
    public readonly string $icon;
    public readonly ?string $lowerLeftIcon;
    public readonly ?string $lowerRightIcon;

    public function __construct(
        EnumsLibraryIcon $icon,
        public string $color = 'fill-black',
        ?EnumsLibraryIcon $lowerLeftIcon = null,
        public string $lowerLeftColor = 'fill-black',
        ?EnumsLibraryIcon $lowerRightIcon = null,
        public string $lowerRightColor = 'fill-black',
    ) {
        $this->icon = $icon->value;
        $this->lowerLeftIcon = $lowerLeftIcon?->value;
        $this->lowerRightIcon = $lowerRightIcon?->value;
    }

    public function render(): View|Closure|string
    {
        return view('components.library-icon');
    }

}
