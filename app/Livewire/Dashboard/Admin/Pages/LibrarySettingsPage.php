<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use App\Models\PartLicense;
use App\Settings\LibrarySettings;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Livewire\Attributes\Layout;
use Livewire\Component;

class LibrarySettingsPage extends Component implements HasForms
{
    use InteractsWithForms;
    
    public ?array $data = [];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('tabs')
                    ->tabs([
                        Tabs\Tab::make('General Settings')
                            ->schema([
                                Select::make('default_part_license_id')
                                    ->options(PartLicense::pluck('name', 'id'))
                                    ->required()
                                    ->label('Default Part License'),
                                TextInput::make('quick_search_limit')
                                    ->label('Max Items for Quick Search')
                                    ->required()
                                    ->integer(),
                           ]),
                        Tabs\Tab::make('Parser Settings')
                            ->schema([
                                Repeater::make('allowed_header_metas')
                                    ->simple(
                                        TextInput::make('meta')
                                            ->string()
                                    ),
                                Repeater::make('allowed_body_metas')
                                    ->simple(
                                        TextInput::make('meta')
                                            ->string()
                                    ),
                            ]),
                        Tabs\Tab::make('Pattern Codes')
                            ->schema([
                                KeyValue::make('pattern_codes')
                                    ->label('Pattern Codes')
                                    ->keyLabel('Code')
                                    ->valueLabel('Pattern Description'),
                           ]),
                        Tabs\Tab::make('LDView Settings')
                            ->schema([
                                KeyValue::make('ldview_options')
                                    ->label('LDView Options')
                                    ->keyLabel('Setting'),
                                FieldSet::make('Image Size')
                                    ->schema([
                                        TextInput::make('max_render_height')
                                            ->required()
                                            ->integer(),
                                        TextInput::make('max_render_width')
                                            ->required()
                                            ->integer(),
                                        TextInput::make('max_thumb_height')
                                            ->required()
                                            ->integer(),
                                        TextInput::make('max_thumb_width')
                                            ->required()
                                            ->integer(),
                                        
                                    ])
                                    ->columns(2)
                            ]),
                            
                    ])
            ])
            ->statePath('data');
    }

    public function mount(LibrarySettings $settings)
    {
        $form_data = [
            'ldview_options' => $settings->ldview_options,
            'max_render_height' => $settings->max_render_height,
            'max_render_width' => $settings->max_render_width,
            'max_thumb_height' => $settings->max_thumb_height,
            'max_thumb_width' => $settings->max_thumb_width,
            'allowed_header_metas' => $settings->allowed_header_metas,
            'allowed_body_metas' => $settings->allowed_body_metas,
            'default_part_license_id' => $settings->default_part_license_id,
            'pattern_codes' => $settings->pattern_codes,
            'quick_search_limit' => $settings->quick_search_limit,
        ];
        $this->form->fill($form_data);
    }
    
    public function saveSettings()
    {
        $form_data = $this->form->getState();
        $settings = app(LibrarySettings::class);
        $settings->ldview_options = $form_data['ldview_options'];
        $settings->max_render_height = $form_data['max_render_height'];
        $settings->max_render_width = $form_data['max_render_width'];
        $settings->max_thumb_height = $form_data['max_thumb_height'];
        $settings->max_thumb_width = $form_data['max_thumb_width'];
        $settings->allowed_header_metas = $form_data['allowed_header_metas'];
        $settings->allowed_body_metas = $form_data['allowed_body_metas'];
        $settings->pattern_codes = $form_data['pattern_codes'];
        $settings->quick_search_limit = $form_data['quick_search_limit'];
        $settings->default_part_license_id = $form_data['default_part_license_id'];
        $settings->save();
    }
    
    #[Layout('components.layout.admin')]
    public function render()
    {
        return view('livewire.dashboard.admin.pages.library-settings-page');
    }
}