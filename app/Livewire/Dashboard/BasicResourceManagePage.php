<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Tables\BasicTable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;

abstract class BasicResourceManagePage extends BasicTable
{
    public string $title = '';
    protected string $menu = 'user';

    #[Layout('components.layout.admin')]
    public function render(): View
    {
        return view('livewire.dashboard.basic-resource-manage-page', ['menu' => $this->menu]);
    }
}
