<?php

namespace App\View\Components;

use App\Models\Document\Document;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TableOfContents extends Component
{
    public array $toc;

    /**
     * Create a new component instance.
     */
    public function __construct(
        protected Document $document
    ) {
        $this->toc = $this->table_of_contents();
    }

    protected function table_of_contents(): array
    {
        $toc = [];
        $pattern = '#<a\h+name="([a-z-0-9_]+)"(?:.*?)><\/a>(?:.*?)<h(\d)(?:.*?)>(.*?)<\/h\d>#ius';
        preg_match_all($pattern, $this->document->content, $match);
        if (!empty($match[0])) {
            foreach ($match[0] as $key => $value) {
                $toc[] = array('level' => $match[2][$key], 'anchor' => $match[1][$key], 'heading' => $match[3][$key]);
            }
        }
        return $toc;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.table-of-contents');
    }
}
