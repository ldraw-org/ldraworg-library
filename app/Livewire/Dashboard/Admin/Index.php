<?php

namespace App\Livewire\Dashboard\Admin;

use App\Enums\Permission;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url]
    public string $activeTab = 'admin-ready';

    public function mount(): void
    {
        $this->authorize(Permission::AdminDashboardView);
    }

    #[Layout('components.layout.admin')]
    public function render()
    {
        return view('livewire.dashboard.admin.index');
    }
}
