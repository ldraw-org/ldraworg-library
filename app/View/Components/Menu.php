<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\Part\Part;
use App\Services\Menu\MenuRegistry;
use Illuminate\Support\Facades\Auth;

class Menu extends Component
{
    public array $items;
    public int $depth = 0;

    public function __construct(string $type, int $depth = 0)
    {
        $this->items = match ($type) {
            'tracker' => MenuRegistry::tracker(),
            'omr' => MenuRegistry::omr(),
            'admin' => MenuRegistry::admin(),
            default => MenuRegistry::library(),
        };
        $this->depth = $depth;
    }

    public function render()
    {
        return view('components.menu.index');
    }

}