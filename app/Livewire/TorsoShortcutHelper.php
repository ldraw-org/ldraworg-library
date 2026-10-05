<?php

namespace App\Livewire;

use App\Enums\ExternalSite;
use App\Enums\PartType;
use App\Models\Part\PartKeyword;
use App\Services\Part\Submit\Registrar;
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
                                ->default(true),
                            Select::make('torso')
                                ->options(fn (Get $get) => $this->torsoOptions($get('unusedTorsos')))
                                ->preload()
                                ->searchable()
                                ->required(),
                            Select::make('template')
                                ->options($this->templateOptions)
                                ->preload()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (Select $c) {
                                    unset($this->template);
                                }),
                        ])
                        ->afterValidation(fn (Set $set, Get $get) => $this->setStep2Values($set, $get)),
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
                            $this->externalsSiteSection(),
                            TextInput::make('keywords')
                                ->required()
                                ->extraAttributes(['class' => 'font-mono'])
                                ->rules(fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $file = [
                                        $get('description'),
                                        "0 Name: " . $get('name'),
                                        PartType::Part->ldrawString(true),
                                        PartCategory::MinifigUpper->ldrawString(),
                                        "0 !KEYWORDS {$value}"
                                    ];
                                    $p = new ParsedPartCollection(implode("\n", $file));
                                    $errors = app(PartChecker::class)->runSingle(PatternHasSetKeyword::class, $p);
                                    if ($errors->isNotEmpty()) {
                                        $fail($errors->first()->message());
                                    }
                                }),
                            $this->partInputs(),
                        ])
                        ->afterValidation(function (Get $get, Set $set) {
                            $set('new_part', $this->torsoText($get));
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
        $file = LDrawFile::fromArray(
            [
                'mimetype' => 'text/plain',
                'filename' => $data['name'],
                'contents' => $data['new_part']
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

    protected function partInputs(): Fieldset
    {
        $partFields = [];
        foreach (Arr::get($this->template, 'parts') ?? [] as $index => $part) {
            if (Str::startsWith($part['name'], '973')) {
                continue;
            }
            $name = basename($part['name'], '.dat');
            $partFields[] = LDrawColourSelect::make("part_{$name}_{$index}_color")
                ->label("Color of {$name} - {$part['description']}");
        }
        return Fieldset::make('Parts')
            ->columns(1)
            ->schema($partFields);
    }

    protected function torsoText(Get $get): string
    {
        if (Arr::get($this->template, 'parts') === null) {
            return '';
        }
        $user = Auth::user();
        $keywords = [];
        foreach (ExternalSite::cases() as $site) {
            $value = $get($site->value);
            if ($value !== null) {
                $keywords[] = $site->name . ' ' . $value;
            }
        }
        $keywords[] = $get('keywords');

        $text = [
            "0 {$get('description')}",
            "0 Name: {$get('name')}",
            $user->toString(),
            PartType::Part->ldrawString(true),
            $user->license->ldrawString(),
            '',
            '0 BFC CERTIFY CCW',
            '',
            PartCategory::MinifigUpper->ldrawString(),
            "0 !KEYWORDS " . implode(', ', $keywords),
            '',
        ];
        foreach ($this->template['parts'] as $index => $part) {
            $name = basename($part['name'], '.dat');
            $color = $get("part_{$name}_{$index}_color") ?? 16;
            $text[] = "1 {$color} {$part['position']} {$part['name']}";
        }
        $text[] = '';
        $fileText = implode("\n", $text);
        $this->parts = app(LDrawModelMaker::class)->webGl($fileText);
        $this->dispatch('render-model');
        return $fileText;
    }

    // Form setup functions
    protected function setStep2Values(Set $set, Get $get)
    {
        $torso = Part::find($get('torso'));
        $set('description', $this->template['description'] . str_replace('Minifig Torso', '', $torso->description));
        $set('name', basename($this->template['name'], '.dat') . str_replace('973', '', basename($torso->filename)));

        $pattern = '/^(' . implode('|', ExternalSite::prefixes()) . ')/i';
        $kws = $torso->keywords->filter(
            fn (PartKeyword $kw) =>
            !preg_match($pattern, $kw->keyword)
        );
        $set('keywords', $kws->sortBy('keyword')->implode('keyword', ', '));

        $set('bricklink', $torso->getExternalSiteNumber(ExternalSite::BrickLink));
        $set('brickowl', $torso->getExternalSiteNumber(ExternalSite::BrickOwl));
        $set('brickset', $torso->getExternalSiteNumber(ExternalSite::Brickset));
        $set('rebrickable', $torso->getExternalSiteNumber(ExternalSite::Rebrickable));
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
    protected function template(): ?array
    {
        if (Arr::get($this->data, 'template')) {
            $part = Part::with(['body', 'subparts'])->find($this->data['template']);
            $template['name'] = $part->meta_name;
            $template['description'] = $part->description;
            $file = new ParsedPartCollection($part->body->body);
            $template['parts'] = [];
            foreach ($file->where('linetype', 1) as $line) {
                $position = [
                    $line['x1'], $line['y1'], $line['z1'],
                    $line['a'], $line['b'], $line['c'],
                    $line['d'], $line['e'], $line['f'],
                    $line['g'], $line['h'], $line['i'],
                ];
                $template['parts'][] = [
                    'name' => $line['file'],
                    'description' => $part->subparts->firstWhere('meta_name', $line['file'])->description,
                    'position' => implode(' ', $position),
                ];
            }
            return $template;
        }

        return null;
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.torso-shortcut-helper');
    }
}
