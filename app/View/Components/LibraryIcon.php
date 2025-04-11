<?php

namespace App\View\Components;

use App\Enums\LibraryIcon as EnumsLibraryIcon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class LibraryIcon extends Component
{
    public function __construct(
        public string|EnumsLibraryIcon $icon,
        public string $color = 'fill-black',
        public ?string $lowerLeftIcon = null,
        public string $lowerLeftColor = 'fill-black',
        public ?string $lowerRightIcon = null,
        public string $lowerRightColor = 'fill-black',
    )
    {
        $this->icon = $this->getIconValue($this->icon);
        if ($this->lowerLeftIcon) {
            $this->lowerLeftIcon = $this->getIconValue($this->lowerLeftIcon);
        }
        if ($this->lowerRightIcon) {
            $this->lowerRightIcon = $this->getIconValue($this->lowerRightIcon);
        }    
    }

    protected function getIconValue(string|EnumsLibraryIcon $icon)
    {
        if ($icon instanceof EnumsLibraryIcon) {
            return $icon->value;
        }
        $icon = Str::studly($icon);
        return EnumsLibraryIcon::{$icon}->value;
    }

    public function render(): View|Closure|string
    {
        return view('components.library-icon');
    }

}
