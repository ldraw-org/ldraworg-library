<?php

namespace App\Http\Livewire\Part;

use Livewire\Component;
use App\Models\Part;

class DeleteFlagButton extends Component
{
    public Part $part;

    protected $rules = [
        'part.delete_flag' => 'required|boolean',
    ];

    public function render()
    {
        return view('livewire.part.delete-flag-button');
    }
}