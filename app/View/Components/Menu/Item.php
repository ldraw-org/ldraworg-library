<?php

namespace App\View\Components\Menu;

use Illuminate\View\Component;
use App\Services\Menu\Contracts\Navigable;

class Item extends Component
{
    public string $label;
    public string $link;
    public bool $visible;
    public bool $hasChildren;
    public array $children;
    public bool $isTopLevel;

    public function __construct(
        public mixed $item, 
        public int $depth = 0
    ) {
        // Normalize the data
        if ($item instanceof Navigable) {
            $this->label = $item->label();
            $this->link  = $item->route();
            $this->visible = $item->visible();
            $this->children = [];
        } else {
            $this->label = $item['label'] ?? '';
            $this->link  = $item['link'] ?? '#';
            $this->visible = $item['visible'] ?? true;
            $this->children = $item['children'] ?? [];
        }

        $this->hasChildren = !empty($this->children);
        $this->isTopLevel = ($depth === 0);
    }

    public function shouldRender(): bool
    {
        return $this->visible;
    }

    public function isActive(): bool
    {
        // Simple exact match check
        if ($this->link !== '#' && request()->url() === $this->link) {
            return true;
        }

        // Recursive check: If I have children, am I "active" because they are?
        foreach ($this->children as $child) {
            if ($this->isChildActive($child)) return true;
        }

        return false;
    }

    protected function isChildActive($item): bool
    {

        $link = $item instanceof Navigable ? $item->route() : ($item['link'] ?? '#');
        if ($link !== '#' && request()->url() === $link) return true;

        if (is_array($item) && isset($item['children'])) {
            foreach ($item['children'] as $child) {
                if ($this->isChildActive($child)) return true;
            }
        }
        return false;
    }

    public function render()
    {
        return view('components.menu.item');
    }
}
