<?php

namespace App\View\Components\Part;

use Illuminate\Contracts\View\View;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Attribution extends Component
{
    public User $copyuser;
    public Collection $editusers;

    public function __construct(
        Part $part
    ) {
        $this->copyuser = $part->user;
        $this->editusers = $part->attributionEditors();
    }

    public function render(): View|Closure|string
    {
        return view('components.part.attribution');
    }
}
