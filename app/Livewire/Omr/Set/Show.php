<?php

namespace App\Livewire\Omr\Set;

use App\LDraw\LDrawModelMaker;
use App\Models\Omr\Set;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Show extends Component
{
    public Set $set;
    public array $parts = ['model.ldr' => ''];
    public ?int $model_id = null;

    public function mount(?Set $set, ?Set $setnumber) {
        if (!is_null($set) && $set->exists) {
            $this->set = $set;
        } elseif (!is_null($setnumber) && $setnumber->exists) {
            $this->set = $setnumber;
        } else {
            return response('404');
        }
    }

    public function openModal(int $id): void
    {
        if ($id != $this->model_id) {
            $this->parts = app(LDrawModelMaker::class)->webGl(Storage::disk('library')->get("/omr/{$this->set->models->find($id)->filename()}"));
            $this->model_id = $id;
        }
        $this->dispatch('open-modal', id: 'ldbi');
    }

    #[Layout('components.layout.omr')]
    public function render()
    {
        return view('livewire.omr.set.show');
    }
}
