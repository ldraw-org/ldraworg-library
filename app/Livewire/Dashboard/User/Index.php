<?php

namespace App\Livewire\Dashboard\User;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url]
    public string $activeTab = 'user-parts';

    #[Layout('components.layout.base')]
    public function render()
    {
        return view('livewire.dashboard.user.index');
    }
}
