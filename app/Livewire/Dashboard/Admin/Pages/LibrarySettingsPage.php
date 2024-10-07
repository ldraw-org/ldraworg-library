<?php

namespace App\Livewire\Dashboard\Admin\Pages;

use App\Jobs\UpdateImage;
use App\Models\Part;
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
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property Form $form
 */
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
                                Toggle::make('tracker_locked'),
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
                                KeyValue::make('default_render_views')
                                    ->label('Default Render Matrix')
                                    ->keyLabel('Part')
                                    ->valueLabel('Matrix'),
                                FieldSet::make('Image Size')
                                    ->schema([
                                        TextInput::make('max_part_render_height')
                                            ->required()
                                            ->integer(),
                                        TextInput::make('max_part_render_width')
                                            ->required()
                                            ->integer(),
                                        TextInput::make('max_model_render_height')
                                            ->required()
                                            ->integer(),
                                        TextInput::make('max_model_render_width')
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
            'tracker_locked' => $settings->tracker_locked,
            'ldview_options' => $settings->ldview_options,
            'default_render_views' => $settings->default_render_views,
            'max_part_render_height' => $settings->max_part_render_height,
            'max_part_render_width' => $settings->max_part_render_width,
            'max_model_render_height' => $settings->max_model_render_height,
            'max_model_render_width' => $settings->max_model_render_width,
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

    public function saveSettings(LibrarySettings $settings)
    {
        $form_data = $this->form->getState();
        $view_changes = [];
        $settings->tracker_locked = $form_data['tracker_locked'];
        $settings->ldview_options = $form_data['ldview_options'];
        if ($settings->default_render_views != $form_data['default_render_views']) {
            $new = array_diff_assoc($form_data['default_render_views'], $settings->default_render_views);
            $old = array_diff_assoc($settings->default_render_views, $form_data['default_render_views']);
            $view_changes = array_merge(array_keys($new), array_keys($old));
            $settings->default_render_views = $form_data['default_render_views'];
        }
        $settings->default_render_views = $form_data['default_render_views'];
        $settings->max_part_render_height = $form_data['max_part_render_height'];
        $settings->max_part_render_width = $form_data['max_part_render_width'];
        $settings->max_model_render_height = $form_data['max_model_render_height'];
        $settings->max_model_render_width = $form_data['max_model_render_width'];
        $settings->max_thumb_height = $form_data['max_thumb_height'];
        $settings->max_thumb_width = $form_data['max_thumb_width'];
        $settings->allowed_header_metas = $form_data['allowed_header_metas'];
        $settings->allowed_body_metas = $form_data['allowed_body_metas'];
        $settings->pattern_codes = $form_data['pattern_codes'];
        $settings->quick_search_limit = $form_data['quick_search_limit'];
        $settings->default_part_license_id = $form_data['default_part_license_id'];
        $settings->save();
        foreach ($view_changes as $part) {
            Part::whereRelation('type', 'folder', 'parts/')
                ->where('filename', 'LIKE', "%{$part}%")
                ->each(fn (Part $p) => UpdateImage::dispatch($p));
        }

    }

    #[Layout('components.layout.admin')]
    public function render()
    {
        return view('livewire.dashboard.admin.pages.library-settings-page');
    }
}
