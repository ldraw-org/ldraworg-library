<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use App\Enums\License;
use App\Enums\Permission;
use App\Settings\LibrarySettings;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property \Filament\Schemas\Schema $form
 */
class LibrarySettingsPage extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];
    public string $title = "Manage Library Settings";

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('tabs')
                    ->tabs([
                        Tab::make('General Settings')
                            ->schema([
                                Toggle::make('tracker_locked'),
                                Select::make('default_part_license')
                                    ->options(License::options())
                                    ->required()
                                    ->label('Default Part License'),
                                TextInput::make('quick_search_limit')
                                    ->label('Max Items for Quick Search')
                                    ->required()
                                    ->integer(),
                           ]),
                        Tab::make('LDView Settings')
                            ->schema([
                                KeyValue::make('ldview_options')
                                    ->label('LDView Options')
                                    ->keyLabel('Setting'),
                            ]),

                    ])
            ])
            ->statePath('data');
    }

    public function mount(LibrarySettings $settings)
    {
        $this->authorize(Permission::SiteSettingsEdit);
        $form_data = [
            'tracker_locked' => $settings->tracker_locked,
            'ldview_options' => $settings->ldview_options,
            'default_part_license' => $settings->default_part_license,
            'quick_search_limit' => $settings->quick_search_limit,
        ];
        $this->form->fill($form_data);
    }

    public function saveSettings(LibrarySettings $settings)
    {
        $form_data = $this->form->getState();
        $settings->tracker_locked = $form_data['tracker_locked'];
        $settings->ldview_options = $form_data['ldview_options'];
        $settings->quick_search_limit = $form_data['quick_search_limit'];
        $settings->default_part_license = $form_data['default_part_license'];
        $settings->save();
    }

    #[Layout('components.layout.admin')]
    public function render()
    {
        return view('livewire.dashboard.admin.pages.library-settings-page');
    }
}
