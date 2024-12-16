<?php

namespace App\Livewire;

use App\Events\PartSubmitted;
use App\Filament\Forms\Components\LDrawColourSelect;
use App\Jobs\UpdateZip;
use App\Models\Part\Part;
use Closure;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property Form $form
 */
class TorsoShortcutHelper extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public array $parts = [];

    protected array $templates = [
        'parts/102195.dat',
        'parts/16360.dat',
        'parts/76382.dat',
        'parts/10677.dat',
        'parts/11398.dat',
        'parts/12896.dat',
        'parts/24319.dat',
        'parts/34415.dat',
        'parts/63208.dat',
        'parts/66614.dat',
        'parts/84638.dat',
        'parts/97149.dat',
        'parts/87858.dat',
        'parts/98642.dat',
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Torso and Template')
                        ->schema([
                            Toggle::make('all-torsos')
                                ->label('Show all torsos'),
                            Select::make('torso')
                                ->options(function(Get $get) {
                                    $parts = Part::when(
                                            !$get('all-torsos'),
                                            fn (Builder $query2) => $query2->whereDoesntHave('parents', fn (Builder $query): Builder =>
                                                $query->where('description', 'LIKE', 'Minifig Torso%')
                                                    ->whereRelation('category', 'category', '<>', 'Sticker Shortcut')
                                            )
                                        )
                                        ->doesntHave('unofficial_part')
                                        ->where('filename', 'LIKE', 'parts/973p%.dat')
                                        ->where('description', 'NOT LIKE', '~%')
                                        ->whereRelation('category', 'category', '<>', 'Sticker Shortcut')
                                        ->orderBy('filename')
                                        ->get();
                                    $options = [];
                                    foreach($parts as $part) {
                                        $name = basename($part->filename, '.dat');
                                        $options[$part->id] = "{$name} - {$part->description}";
                                    }
                                    return $options;
                                })
                                ->preload()
                                ->searchable()
                                ->required(),
                            Select::make('template')
                                ->options(function() {
                                    $parts = Part::whereIn('filename', $this->templates)
                                        ->orderBy('description')
                                        ->get();
                                    $options = [];
                                    foreach($parts as $part) {
                                        $name = basename($part->filename, '.dat');
                                        $options[$part->id] = "{$name} - {$part->description}";
                                    }
                                    return $options;
                                })
                                ->preload()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function(Select $c) {
                                    unset($this->template);
                                    unset($this->selectParts);
                                }),
                        ])
                        ->afterValidation(function (Set $set) {
                            $p = Part::with('keywords')->find($this->data['torso']);
                            $set('description', $this->template->description . str_replace('Minifig Torso', '', $p->description));
                            $set('name', basename($this->template->filename, '.dat') . str_replace('973', '', basename($p->filename)));
                            $set('keywords', implode(', ', $p->keywords->pluck('keyword')->all()));
                            foreach($this->templateParts() as $index => $tpart) {
                                $index++;
                                $set("part_{$index}_color", '16');
                                $set("part_{$index}_id", $this->selectParts[$tpart]['default']);
                            }
                        }),
                    Wizard\Step::make('New Shortcut Details')
                        ->schema([
                            TextInput::make('description')
                                ->required()
                                ->rules([
                                    fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                        $p = Part::where('description', $value)->partsFolderOnly()->first();
                                        if (!is_null($p)) {
                                            $fail('A part with that description already exists');
                                        }
                                    },
                                ]),
                            TextInput::make('name')
                                ->required()
                                ->rules([
                                    fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                        $p = Part::firstWhere('filename', "parts/{$value}");
                                        if (!is_null($p)) {
                                            $fail('A part of the name already exists');
                                        }
                                    },
                                ]),
                            TextInput::make('keywords')
                                ->required()
                                ->rules([
                                    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        $kws = explode(', ', $value);
                                        if (! app(\App\LDraw\Check\PartChecker::class)->checkPatternForSetKeyword($get('name'), $kws)) {
                                            $fail(__('partcheck.keywords'));
                                        }
                                    },
                                ]),
                            $this->partInput(1),
                            $this->partInput(2),
                            $this->partInput(3),
                            $this->partInput(4),
                        ])
                        ->afterValidation(function (Set $set) {
                            $set('new_part', $this->makeShortcut());
                        }),
                    Wizard\Step::make('Review and Submit')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Textarea::make('new_part')
                                        ->autosize()
                                        ->readOnly()
                                        ->extraAttributes(['class' => 'font-mono']),
                                    View::make('forms.3d-view')
                                        ->viewData([
                                            'parts' => $this->parts,
                                            'partname' => 'model.ldr',
                                        ])
                                ])
                        ]),
                ])
                    ->submitAction(new HtmlString(Blade::render("<x-filament::button type=\"submit\">\n<x-filament::loading-indicator wire:loading wire:target=\"submitFile\" class=\"h-5 w-5\" />\nSubmit\n</x-filament::button>")))

            ])
            ->statePath('data');
    }

    public function submitFile()
    {
        if (auth()->user()->cannot('create', Part::class)) {
            return;
        }
        $pm = app(\App\LDraw\PartManager::class);
        $file = [
            [
                'type' => 'text',
                'filename' => $this->data['name'],
                'contents' => $this->makeShortcut()
            ]
        ];
        $p = $pm->submit($file, auth()->user());
        $newpart = $p->first();
        UpdateZip::dispatch($newpart);
        PartSubmitted::dispatch($newpart, auth()->user());
        $this->redirectRoute('parts.show', $newpart);
    }

    protected function partInput(int $index): Fieldset
    {
        return Fieldset::make()
            ->label("Part {$index}")
            ->schema([
                LDrawColourSelect::make("part_{$index}_color")
                    ->label('Color'),
                Select::make("part_{$index}_id")
                    ->label('Name - Description')
                    ->options(fn () => array_key_exists($index-1, $this->templateParts()) ? $this->selectParts[$this->templateParts()[$index-1]]['subs'] : [])
                    ->selectablePlaceholder(false)
                    ->preload(),
            ])
            ->hidden(!array_key_exists($index-1, $this->templateParts()));
    }

    #[Computed(persist: true)]
    protected function template(): ?Part
    {
        if (array_key_exists('template', $this->data)) {
            return Part::with('body', 'subparts')->find($this->data['template']);
        }

        return null;
    }

    protected function templateParts(): array
    {
        $tparts = [];
        if (!is_null($this->template)) {
            $pattern = '#^\h*1\h+16\h+((?:[\d.-]+\h+){12})(?P<subpart>[\/a-z0-9_.\\\\-]+)\h*?$#um';
            preg_match_all($pattern, $this->template->body->body, $subs);
            array_shift($subs['subpart']);
            return $subs['subpart'];
        }
        return $tparts;
    }

    #[Computed]
    protected function selectParts(): array
    {
        $parts = [];
        if (!is_null($this->template)) {
            foreach($this->template->subparts as $subpart)
            {
                if ($subpart->filename != 'parts/973.dat') {
                    $name = basename($subpart->filename);
                    $parts[$name] = ['default' => $subpart->id, 'subs' => [$subpart->id => "{$name} - {$subpart->description}"]];
                    $pats = $subpart->suffix_parts->where('is_pattern', true)->where('category.category', '!=', 'Moved');
                    foreach($pats as $pat) {
                        $patname = basename($pat->filename);
                        $parts[$name]['subs'][$pat->id] = "{$patname} - {$pat->description}";
                    }
                }
            }
        }
        return $parts;
    }

    #[Computed]
    protected function colors(): array
    {
        if (Storage::disk('library')->exists('official/LDConfig.ldr')) {
            $ldconfig = Storage::disk('library')->get('official/LDConfig.ldr');
            $ldconfig = preg_replace("#\R#", "\n", $ldconfig);
            $colour_pattern = "/^\h*0\h+!COLOUR\h+(?<name>[A-Za-z_]+)\h+CODE\h+(?<code>\d+)\h+VALUE\h+(?<value>(?:#|0x)[A-Fa-f\d]{6})\h+EDGE\h+(?<edge>\d+|(?:#|0x)[A-Fa-f\d]{6})(?:\h+ALPHA\h+(?<alpha>\d{1,3}))?(?:\h+LUMINANCE\h+(?<luminance>\d+))?(?:\h+(?<material>CHROME|METAL|PEARLESCENT|RUBBER|MATERIAL\h+.*))?\h*$/im";
            if (preg_match_all($colour_pattern, $ldconfig, $colours, PREG_SET_ORDER)) {
                $options = [];
                foreach($colours as $color) {
                    $int = hexdec($color['value']);
                    $rgb = array("red" => ((0xFF & ($int >> 0x10)) / 255.0), "green" => ((0xFF & ($int >> 0x8)) / 255.0), "blue" => ((0xFF & $int) / 255.0));
                    foreach ($rgb as $tcolor => $value) {
                        if ($value <= 0.03928) {
                          $rgb[$tcolor] = $value / 12.92;
                        }
                        else {
                          $rgb[$tcolor] = (($value + 0.055) / 1.055) ** 2.4;
                        }
                    }
                    $L = 0.2126 * $rgb['red'] + 0.7152 * $rgb['green'] + 0.0722 * $rgb['blue'];
                    if ($L > 0.179) {
                        $text = 'text-gray-900';
                    } else {
                        $text = 'text-gray-50';
                    }
                    $options[$color['code']] = "<span class=\"{$text} rounded px-2 py-1\" style=\"background-color: {$color['value']}\">{$color['code']} - {$color['name']}</span>";
                }
                return $options;
            }
        }
        return [];
    }

    protected function makeShortcut(): string
    {
        $text = "0 {$this->data['description']}\n";
        $text .= "0 Name: {$this->data['name']}\n";
        $u = auth()->user();
        $text .= "0 Author: {$u->authorString}\n";
        $text .= "0 !LDRAW_ORG Unofficial_Shortcut\n";
        $text .= $u->license->ldrawString() . "\n\n";
        $kws = explode(', ', $this->data['keywords']);
        $kwline = '';
        foreach ($kws as $index => $kw) {
            if (array_key_first($kws) == $index) {
                $kwline = "0 !KEYWORDS ";
            }
            if ($kwline !== "0 !KEYWORDS " && mb_strlen("{$kwline}, {$kw}") > 80) {
                $text .= "{$kwline}\n";
                $kwline = "0 !KEYWORDS ";
            }
            if ($kwline !== "0 !KEYWORDS ") {
                $kwline .= ", ";
            }
            $kwline .= $kw;
            if (array_key_last($kws) == $index) {
                $text .= "{$kwline}\n";
            }
        }
        $text .= "\n0 BFC CERTIFY CCW\n\n";

        $pattern = '#^\h*1\h+16\h+((?:[\d.-]+\h+){12})(?P<subpart>[\/a-z0-9_.\\\\-]+)\h*?$#um';
        preg_match_all($pattern, $this->template->body->body, $matrix);
        $matrix = $matrix[1];
        array_shift($matrix);
        $p = Part::find($this->data['torso']);
        $text .= "1 16 0 0 0 1 0 0 0 1 0 0 0 1 {$p->name()}\n";
        foreach($this->templateParts() as $index => $tpart) {
            $index++;
            $p = Part::find($this->data["part_{$index}_id"]);
            $text .= '1 ' . $this->data["part_{$index}_color"] . ' ' . $matrix[$index-1] . $p->name() . "\n";
        }
        $this->parts = app(\App\LDraw\LDrawModelMaker::class)->webGl($text);
        $this->dispatch('render-model');
        return $text;
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.torso-shortcut-helper');
    }
}
