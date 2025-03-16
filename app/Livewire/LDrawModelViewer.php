<?php

namespace App\Livewire;

use App\LDraw\LDrawModelMaker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property Form $form
 */
class LDrawModelViewer extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public string $modeltext = '';
    public array $parts = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('ldraw-model')
                    ->storeFiles(false)
                    ->minFiles(1)
            ])
            ->statePath('data');
    }

    public function makeModel()
    {
        if (count($this->data['ldraw-model']) != 1) {
            return;
        }
        $model = array_pop($this->data['ldraw-model'])->get();
        $this->parts = app(LDrawModelMaker::class)->webGl($model);
        $this->dispatch('render-model');
    }

    #[Layout('components.layout.base')]
    public function render()
    {
        return view('livewire.ldraw-model-viewer');
    }
}
