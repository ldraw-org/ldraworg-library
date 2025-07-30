<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use App\Enums\Permission;
use App\LDraw\LDrawColourManager;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

class LdconfigEdit extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->authorize(Permission::LdconfigEdit);
        $this->form->fill([
            'ldconfig-text' => Storage::disk('library')->get('official/LDConfig.ldr') ?? '',
            'ldcfgalt-text' => Storage::disk('library')->get('official/LDCfgalt.ldr') ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('LDConfig_Files')
                    ->tabs([
                        Tabs\Tab::make('LDConfig')
                            ->schema([
                                Textarea::make('ldconfig-text')
                                    ->label('Last Edited: ' . (new Carbon(Storage::disk('library')->lastModified('official/LDConfig.ldr')))->format('Y-m-d'))
                                    ->rows(20)
                                    ->extraAttributes(['class' => 'font-mono'])
                                    ->required()
                            ]),
                            Tabs\Tab::make('LDCfgalt')
                            ->schema([
                                Textarea::make('ldcfgalt-text')
                                    ->label('Last Edited: ' . (new Carbon(Storage::disk('library')->lastModified('official/LDCfgalt.ldr')))->format('Y-m-d'))
                                    ->rows(20)
                                    ->extraAttributes(['class' => 'font-mono'])
                                    ->required()
                            ])
                    ])
            ])
            ->statePath('data');
    }

    public function updateFiles(): void
    {
        $this->authorize(Permission::LdconfigEdit);
        $data = $this->form->getState();
        $old_ldconfig = Storage::disk('library')->get('official/LDConfig.ldr') ?? '';
        $old_ldcfgalt = Storage::disk('library')->get('official/LDCfgalt.ldr') ?? '';
        if ($data['ldconfig-text'] != $old_ldconfig) {
            store_backup('LDConfig.ldr', $old_ldconfig);
            Storage::disk('library')->put('official/LDConfig.ldr', $data['ldconfig-text']);
            app(LDrawColourManager::class)->importColours();
        }
        if ($data['ldcfgalt-text'] != $old_ldcfgalt) {
            store_backup('LDCfgalt.ldr', $old_ldcfgalt);
            Storage::disk('library')->put('official/LDCfgalt.ldr', $data['ldcfgalt-text']);
        }
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.dashboard.admin.pages.ldconfig-edit');
    }
}
