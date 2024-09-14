<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class User extends Component
{
    #[Url]
    public string $activeTab = 'user-parts';

    #[Layout('components.layout.base')]
    public function render()
    {
        return view('livewire.dashboard.user');
    }
}
