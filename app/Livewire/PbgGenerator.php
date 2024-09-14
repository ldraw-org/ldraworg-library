<?php

namespace App\Livewire;

use App\LDraw\SetPbg;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class PbgGenerator extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public ?string $pbg = null;

    public bool $hasMessages = false;
    public bool $hasErrors = false;
    public array $errors = [];
    public bool $hasUnpatterned = false;
    public array $unpatterned = [];
    public bool $hasMissing = false;
    public array $missing = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('set-number')
                    ->required()
                    ->string()
            ])
            ->statePath('data');
    }

    public function makePbg(): void
    {
        $data = $this->form->getState();
        $set = $data['set-number'];
        if (!str_ends_with($set, '-1')) {
            $set .= "-1";
        }
        $set_pbg = new SetPbg();
        $this->pbg = $set_pbg->pbg($set);
        $this->hasMessages = $set_pbg->messages->isNotEmpty();
        $this->hasErrors = $set_pbg->messages->has('errors');
        $this->errors = $set_pbg->messages->get('errors');
        $this->hasUnpatterned = $set_pbg->messages->has('unpatterned');
        $this->unpatterned = $set_pbg->messages->get('unpatterned');
        $this->hasMissing = $set_pbg->messages->has('missing');
        $this->missing = $set_pbg->messages->get('missing');
    }

    public function pbgDownload()
    {
        return response()->streamDownload(
            function () {
                echo $this->pbg;
            },
            basename($this->data['set-number'] . '.pbg'),
            [
            'Content-Type' => 'text/plain',
        ]
        );
    }

    #[Layout('components.layout.base')]
    public function render(): View
    {
        return view('livewire.pbg-generator');
    }
}
