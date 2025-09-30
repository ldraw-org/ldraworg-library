<?php

namespace App\Livewire;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Grid;
use App\Services\LDraw\Parse\Parser;
use Filament\Schemas\Components\View;
use App\Services\LDraw\Managers\Part\PartManager;
use Filament\Schemas\Components\Fieldset;
use App\Services\LDraw\LDrawModelMaker;
use App\Enums\PartCategory;
use App\Filament\Forms\Components\LDrawColourSelect;
use App\Services\LDraw\Check\Checks\PatternHasSetKeyword;
use App\Services\LDraw\Check\PartChecker;
use App\Services\LDraw\LDrawFile;
use App\Services\LDraw\Rebrickable;
use App\Models\Part\Part;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property \Filament\Schemas\Schema $form
 */
class TorsoShortcutHelper extends Component implements HasSchemas
{
    use InteractsWithSchemas;

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
        $this->authorize('create', Part::class);
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Torso and Template')
                        ->schema([
                            Toggle::make('all-torsos')
                                ->label('Show all torsos'),
                            Select::make('torso')
                                ->options(function (Get $get) {
                                    $parts = Part::when(
                                        !$get('all-torsos'),
                                        fn (Builder $query2) => $query2->whereDoesntHave(
                                            'parents',
                                            fn (Builder $query): Builder =>
                                            $query->where('description', 'LIKE', 'Minifig Torso%')
                                                ->where('category', '!=', PartCategory::StickerShortcut)
                                        )
                                    )
                                        ->doesntHave('unofficial_part')
                                        ->where('filename', 'LIKE', 'parts/973p%.dat')
                                        ->where('description', 'NOT LIKE', '~%')
                                        ->where('category', '!=', PartCategory::StickerShortcut)
                                        ->orderBy('filename')
                                        ->get();
                                    $options = [];
                                    foreach ($parts as $part) {
                                        $name = basename($part->filename, '.dat');
                                        $options[$part->id] = "{$name} - {$part->description}";
                                    }
                                    return $options;
                                })
                                ->preload()
                                ->searchable()
                                ->required(),
                            Select::make('template')
                                ->options(function () {
                                    $parts = Part::whereIn('filename', $this->templates)
                                        ->orderBy('description')
                                        ->get();
                                    $options = [];
                                    foreach ($parts as $part) {
                                        $name = basename($part->filename, '.dat');
                                        $options[$part->id] = "{$name} - {$part->description}";
                                    }
                                    return $options;
                                })
                                ->preload()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (Select $c) {
                                    unset($this->template);
                                    unset($this->selectParts);
                                }),
                        ])
                        ->afterValidation(function (Set $set, Get $get) {
                            $set('bricklink', null);
                            $set('brickowl', null);
                            $set('rebrickable', null);
                            $p = Part::with('keywords')->find($get('torso'));
                            $set('description', $this->template->description . str_replace('Minifig Torso', '', $p->description));
                            $set('name', basename($this->template->filename, '.dat') . str_replace('973', '', basename($p->filename)));
                            $kws = [];
                            foreach ($p->keywords as $keyword) {
                                $kw = strtolower($keyword->keyword);
                                if (Str::startsWith($kw, ['bricklink ', 'brickowl ', 'rebrickable '])) {
                                    $number = Str::chopStart($kw, ['bricklink ', 'brickowl ', 'rebrickable ']);
                                    $site = Str::words($kw, 1, '');
                                    $set($site, $number);
                                } else {
                                    $kws[] = $keyword->keyword;
                                }
                            }
                            $set('keywords', implode(', ', $kws));
                            if (is_null($get('brickowl')) || is_null($get('bricklink')) || is_null($get('rebrickable'))) {
                                $this->setExternal($get, $set);
                            }
                            foreach ($this->templateParts() as $index => $tpart) {
                                $index++;
                                $set("part_{$index}_color", '16');
                                $set("part_{$index}_id", $this->selectParts[$tpart]['default']);
                            }
                        }),
                    Step::make('New Shortcut Details')
                        ->schema([
                            TextInput::make('description')
                                ->required()
                                ->extraAttributes(['class' => 'font-mono'])
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
                            Grid::make([
                                    'md' => 3
                                ])
                                ->schema([
                                    TextInput::make('rebrickable')
                                    ->string()
                                    ->required()
                                    ->extraAttributes(['class' => 'font-mono']),
                                    TextInput::make('bricklink')
                                        ->string()
                                        ->required()
                                        ->extraAttributes(['class' => 'font-mono']),
                                    TextInput::make('brickowl')
                                        ->string()
                                        ->required()
                                        ->extraAttributes(['class' => 'font-mono']),
                                ]),
                            TextInput::make('keywords')
                                ->required()
                                ->extraAttributes(['class' => 'font-mono'])
                                ->rules([
                                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail) {
                                        $p = app(Parser::class)->parse($this->makeShortcut());
                                        $errors = (new PartChecker($p))->singleCheck(new PatternHasSetKeyword());
                                        if (count($errors) > 0) {
                                            $fail($errors[0]);
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
                    Step::make('Review and Submit')
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

    protected function setExternal(Get $get, Set $set): void
    {
        $rb_num = $get('rebrickable');
        $bl_num = $get('bricklink');
        $bo_num = $get('brickowl');
        if (!Str::endsWith($bl_num, ['c01', 'c02'])) {
            $bl_num .= 'c01';
        }

        $rb = new Rebrickable();
        if (!is_null($rb_num)) {
            $rb_part = $rb->getPart($rb_num);
        } elseif (!is_null($bl_num)) {
            $rb_part = $rb->getParts(['bricklink_id' => $bl_num])?->first();
        } elseif (!is_null($bo_num)) {
            $rb_part = $rb->getParts(['brickowl_id' => $bo_num])?->first();
        } else {
            $rb_part = $rb->getParts(['search' => basename(Part::find($this->data['torso'])->name(), '.dat')])?->first();
        }
        if (!is_null($rb_part) && $rb_part['part_num'] != '3814') {
            $set('rebrickable', $rb_part['part_num']);
            if (Arr::has($rb_part, 'external_ids.BrickLink')) {
                $set('bricklink', Arr::get($rb_part, 'external_ids.BrickLink.0'));
            }
            if (Arr::has($rb_part, 'external_ids.BrickOwl')) {
                $set('brickowl', Arr::get($rb_part, 'external_ids.BrickOwl.0'));
            }
        }
    }

    public function submitFile(): void
    {
        $u = Auth::user();
        if ($u->cannot('create', Part::class)) {
            return;
        }
        $pm = app(PartManager::class);
        $file = LDrawFile::fromArray(
            [
                'mimetype' => 'text/plain',
                'filename' => $this->data['name'],
                'contents' => $this->makeShortcut()
            ]
        );
        $p = $pm->submit($file, $u);
        $newpart = $p->first();
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
                    ->options(fn () => array_key_exists($index - 1, $this->templateParts()) ? $this->selectParts[$this->templateParts()[$index - 1]]['subs'] : [])
                    ->selectablePlaceholder(false)
                    ->preload(),
            ])
            ->hidden(!array_key_exists($index - 1, $this->templateParts()));
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
            foreach ($this->template->subparts as $subpart) {
                if ($subpart->filename != 'parts/973.dat') {
                    $name = basename($subpart->filename);
                    $parts[$name] = ['default' => $subpart->id, 'subs' => [$subpart->id => "{$name} - {$subpart->description}"]];
                    $pats = $subpart->suffix_parts->where('is_pattern', true)->where('category', '!=', PartCategory::Moved);
                    foreach ($pats as $pat) {
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
                foreach ($colours as $color) {
                    $int = hexdec($color['value']);
                    $rgb = array("red" => ((0xFF & ($int >> 0x10)) / 255.0), "green" => ((0xFF & ($int >> 0x8)) / 255.0), "blue" => ((0xFF & $int) / 255.0));
                    foreach ($rgb as $tcolor => $value) {
                        if ($value <= 0.03928) {
                            $rgb[$tcolor] = $value / 12.92;
                        } else {
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
        $u = Auth::user();
        $text .= "0 Author: {$u->authorString}\n";
        $text .= "0 !LDRAW_ORG Unofficial_Shortcut\n";
        $text .= $u->license->ldrawString() . "\n\n";
        $kws = explode(', ', $this->data['keywords']);
        $kws[] = 'Bricklink ' . $this->data['bricklink'];
        $kws[] = 'Rebrickable ' . $this->data['rebrickable'];
        $kws[] = 'BrickOwl ' . $this->data['brickowl'];
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
        foreach ($this->templateParts() as $index => $tpart) {
            $index++;
            $p = Part::find($this->data["part_{$index}_id"]);
            $text .= '1 ' . $this->data["part_{$index}_color"] . ' ' . $matrix[$index - 1] . $p->name() . "\n";
        }
        $this->parts = app(LDrawModelMaker::class)->webGl($text);
        $this->dispatch('render-model');
        return $text;
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.torso-shortcut-helper');
    }
}
