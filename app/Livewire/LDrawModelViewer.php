<?php

namespace App\Livewire;

use App\LDraw\Parse\Parser;
use App\Models\Part;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Attributes\Layout;
use Livewire\Component;

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
                    ->required()
            ])
            ->statePath('data');
    }

    public function makeModel()
    {
        $model = array_pop($this->data['ldraw-model'])->get();
        $this->modeltext = 'data:text/plain;base64,' . base64_encode($model);
        $parts = app(\App\LDraw\Parse\Parser::class)->getSubparts($model);
        $subs = [];
        foreach ($parts['subparts'] ?? [] as $s) {
            $s = str_replace('\\', '/', $s);
            $subs[] = "parts/{$s}";
            $subs[] = "p/{$s}";
        }
        foreach ($parts['textures'] ?? [] as $s) {
            $s = str_replace('\\', '/', $s);
            $subs[] = "parts/textures/{$s}";
            $subs[] = "p/textures/{$s}";
        }
        $mparts = new \Illuminate\Database\Eloquent\Collection();
        foreach (Part::with('descendantsAndSelf')->whereIn('filename', $subs)->get() as $part) {
            $mparts = $mparts->merge($part->descendantsAndSelf);
        }
        $mparts = $mparts->unique();
        foreach($mparts as $p) {
            if ($p->isTexmap()) {
                $pn = str_replace(["parts/textures/","p/textures/"], '', $p->filename);
                $this->parts[$pn] = 'data:image/png;base64,' .  base64_encode($p->get());
            } else {
                $pn = str_replace(["parts/","p/"], '', $p->filename);
                $this->parts[$pn] = 'data:text/plain;base64,' .  base64_encode($p->get());
            }
        }
        $this->dispatch('render-model');
    }

    #[Layout('components.layout.base')]
    public function render()
    {
        return view('livewire.ldraw-model-viewer');
    }
}
