<?php

namespace App\Livewire;

use App\Enums\ExternalSite;
use App\Enums\PartType;
use App\Models\Part\PartKeyword;
use App\Models\User;
use App\Services\Part\Submit\Registrar;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Fieldset;
use App\Services\LDraw\LDrawModelMaker;
use App\Enums\PartCategory;
use App\Filament\Forms\Components\LDrawColourSelect;
use App\Services\LDraw\LDrawFile;
use App\Models\Part\Part;
use App\Services\Check\PartChecker;
use App\Services\Check\PartChecks\PatternHasSetKeyword;
use App\Services\Parser\ParsedPartCollection;
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
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property Schema $form
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
                            Toggle::make('unusedTorsos')
                                ->label('Show only torsos without a shortcut')
                                ->live()
                                ->default(true),
                            Select::make('torso')
                                ->options(fn (Get $get) => $this->torsoOptions($get('unusedTorsos')))
                                ->preload()
                                ->searchable()
                                ->required()
                                ->afterStateUpdated(function (int $state, Set $set) {
                                    unset($this->torso);
                                    $torsoDescription = $this->torso?->description ?? '';
                                    $torsoDescription = Str::chopStart($torsoDescription, 'Minifig Torso');
                                    $description = $this->template['description'] . $torsoDescription;
                                    $set('description', $description);
                                    $name = basename($this->template['name'], '.dat');
                                    $name .= Str::chopStart(basename($this->torso?->meta_name ?? ''), '973');
                                    $set('name', $name);
                                    $pattern = '/^(' . implode('|', ExternalSite::prefixes()) . ')/i';
                                    $kws = $this->torso?->keywords->filter(
                                        fn (PartKeyword $kw) =>
                                        !preg_match($pattern, $kw->keyword)
                                    );
                                    $set('keywords', $kws->sortBy('keyword')->implode('keyword', ', '));
                                    foreach (ExternalSite::cases() as $site) {
                                        $set($site->value, $this->torso?->getExternalSiteNumber($site));
                                    }
                                }),
                            Select::make('template')
                                ->options($this->templateOptions)
                                ->default(Part::firstWhere('filename', 'parts/76382.dat')->id)
                                ->selectablePlaceholder(false)
                                ->preload()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function () {
                                    unset($this->template);
                                    foreach (Arr::get($this->template, 'parts') ?? [] as $index => $part) {
                                        if ($part['variations'] !== []) {
                                            Arr::set($this->data, "part_{$index}_part", null);
                                        }
                                        Arr::set($this->data, "part_{$index}_color", null);
                                    }
                                    $this->form
                                        ->getComponent('partFields')
                                        ->getChildSchema()
                                        ->fill();
                                }),
                        ]),
                    Step::make('New Shortcut Details')
                        ->schema([
                            TextInput::make('description')
                                ->required()
                                ->extraAttributes(['class' => 'font-mono'])
                                ->rules([
                                    fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                        if (Part::where('description', $value)->partsFolderOnly()->exists()) {
                                            $fail('A part with that description already exists');
                                        }
                                    },
                                ]),
                            TextInput::make('name')
                                ->required()
                                ->rules([
                                    fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                        if (Part::where('filename', "parts/{$value}")->exists()) {
                                            $fail('A part of the name already exists');
                                        }
                                    },
                                ]),
                            $this->externalsSiteSection(),
                            TextInput::make('keywords')
                                ->required()
                                ->extraAttributes(['class' => 'font-mono'])
                                ->rules(fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $file = $this->getTorsoTextHeader($get('description'), $get('name'), $value);
                                    $p = new ParsedPartCollection($file);
                                    $errors = app(PartChecker::class)->runSingle(PatternHasSetKeyword::class, $p);
                                    if ($errors->isNotEmpty()) {
                                        $fail($errors->first()->message());
                                    }
                                }),
                            $this->partInputs(),
                        ])
                        ->afterValidation(function (Get $get, Set $set) {
                            $data = $this->form->getState();
                            $fileText = $this->torsoText($data);
                            $set('new_part', $fileText);
                            $this->parts = app(LDrawModelMaker::class)->webGl($fileText);
                            $this->dispatch('render-model');
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

    public function submitFile(): void
    {
        $u = Auth::user();
        if ($u->cannot('create', Part::class)) {
            return;
        }
        $registrar = app(Registrar::class);
        $data = $this->form->getState();
        $fileText = $this->torsoText($data);
        $name = $data['name'];
        if (Str::doesntEndWith($name, '.dat')) {
            $name .= '.dat';
        }
        if (Part::where('filename', "parts/{$name}")->exists()) {
            return;
        }
        $file = LDrawFile::fromArray(
            [
                'mimetype' => 'text/plain',
                'filename' => $name,
                'contents' => $fileText,
            ]
        );

        $p = $registrar->submit(collect([$file]), $u);
        $newPart = $p->first();
        $this->redirectRoute('parts.show', $newPart);
    }

    protected function externalsSiteSection(): Fieldset
    {
        $externalSiteFields = [];
        foreach (ExternalSite::cases() as $site) {
            $otherSites = array_column(
                array_filter(ExternalSite::cases(), fn ($c) => $c !== $site),
                'value'
            );
            $externalSiteFields[] = TextInput::make($site->value)
                ->string()
                ->requiredWithoutAll($otherSites)
                ->extraAttributes(['class' => 'font-mono']);
        };
        return Fieldset::make('External Site Numbers')
            ->columns([
                'default' => 1,
                'md' => count(ExternalSite::cases()),
                'lg' => count(ExternalSite::cases()),
            ])
            ->schema($externalSiteFields);
    }

    protected function partInputs(): Section
    {
        $partFields = [];
        foreach (Arr::get($this->template, 'parts') ?? [] as $index => $part) {
            if (Str::startsWith($part['name'], '973')) {
                continue;
            }
            $partOptions = [];
            $name = basename($part['name'], '.dat');
            if ($part['variations'] !== []) {
                $partOptions[] = Select::make("part_{$index}_part")
                    ->options($part['variations'])
                    ->label('Variation');
            }
            $partOptions[] = LDrawColourSelect::make("part_{$index}_color")
                ->label("Color");
            $partFields[] = Fieldset::make("{$name} - {$part['description']}")
                ->schema($partOptions);
        }
        return Section::make('Parts')
            ->schema($partFields)
            ->key('partFields');
    }

    protected function torsoText(array $data): string
    {
        if (Arr::get($this->template, 'parts') === null) {
            return '';
        }
        $text = [];
        $keywords = [];
        foreach (ExternalSite::cases() as $site) {
            $value = Arr::get($data, $site->value);
            if ($value !== null) {
                $keywords[] = $site->name . ' ' . $value;
            }
        }
        $keywords[] = Arr::get($data, 'keywords');
        $kws = implode(', ', $keywords);
        $description = Arr::get($data, 'description');
        $name = Arr::get($data, 'name');
        if (Str::doesntEndWith($name, '.dat')) {
            $name .= '.dat';
        }
        $text[] = $this->getTorsoTextHeader($description, $name, $kws);
        foreach ($this->template['parts'] as $index => $part) {
            $color = Arr::get($data,"part_{$index}_color") ?? 16;
            $file = Arr::get($data, "part_{$index}_part") ?? $part['name'];
            if (Str::startsWith($part['name'], '973')) {
                $file = Part::find(Arr::get($data, 'torso'))->meta_name;
            }
            $text[] = "1 {$color} {$part['position']} {$file}";
        }
        $text[] = '';
        $fileText = implode("\n", $text);
        return $fileText;
    }

    // Utility methods
    protected function torsoOptions(bool $onlyUnused = false): array
    {
        $torsos = Part::whereLike('filename', 'parts/973%.dat')
            ->when($onlyUnused, function (Builder $query) {
                $query->whereDoesntHave('parents');
            })
            ->doesntHave('unofficial_part')
            ->activeParts()
            ->orderBy('filename')
            ->get()
            ->mapWithKeys(fn (Part $p) => [$p->id => "{$p->meta_name} - {$p->description}"])
            ->toArray();
        return $torsos;
    }

    #[Computed]
    protected function templateOptions(): array
    {
        $templates = Part::whereIn('filename', $this->templates)
            ->orderBy('filename')
            ->get()
            ->mapWithKeys(fn (Part $p) => [$p->id => "{$p->meta_name} - {$p->description}"])
            ->toArray();
        return $templates;
    }

    #[Computed(persist: true)]
    protected function torso(): ?Part
    {
        return Part::find(Arr::get($this->data, 'torso'));
    }

    #[Computed(persist: true)]
    protected function template(): ?array
    {
        if (Arr::get($this->data, 'template')) {
            $part = Part::with(['body', 'subparts', 'subparts.patterns'])->find($this->data['template']);
            $template['name'] = $part->meta_name;
            $template['description'] = $part->description;
            $file = new ParsedPartCollection($part->body->body);
            $template['parts'] = [];
            foreach ($file->where('linetype', 1) as $line) {
                $subpart = $part->subparts->firstWhere('meta_name', $line['file']);
                $position = [
                    $line['x1'], $line['y1'], $line['z1'],
                    $line['a'], $line['b'], $line['c'],
                    $line['d'], $line['e'], $line['f'],
                    $line['g'], $line['h'], $line['i'],
                ];
                if (Str::startsWith($line['file'], '973')) {
                    $variations = [];
                } else {
                    $variations = $subpart->patterns
                        ->whereNull('unofficial_part')
                        ->activeParts()
                        ->mapWithKeys(fn (Part $p) => [$p->meta_name => "{$p->meta_name} - {$p->description}"])
                        ->toArray();
                }
                $template['parts'][] = [
                    'name' => $line['file'],
                    'description' => $subpart->description,
                    'position' => implode(' ', $position),
                    'variations' => $variations,
                ];
            }
            return $template;
        }

        return null;
    }

    #[Computed(persist: true)]
    protected function user(): User
    {
        return Auth::user();
    }

    protected function getTorsoTextHeader(string $description, string $name, string $keywords): string
    {
        $text = [
            "0 $description",
            "0 Name: $name",
            $this->user->toString(),
            PartType::Part->ldrawString(true),
            $this->user->license->ldrawString(),
            '',
            '0 BFC CERTIFY CCW',
            '',
            PartCategory::MinifigUpper->ldrawString(),
            "0 !KEYWORDS " . $keywords,
            '',
        ];
        return implode("\n", $text);
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.torso-shortcut-helper');
    }
}
