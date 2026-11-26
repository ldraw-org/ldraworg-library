<?php

namespace App\Livewire;

use App\Enums\ExternalSite;
use App\Filament\Forms\Components\LDrawColourSelect;
use App\Models\Part\Part;
use App\Services\Check\PartChecker;
use App\Services\Check\PartChecks\PatternHasSetKeyword;
use App\Services\LDraw\LDrawFile;
use App\Services\LDraw\LDrawModelMaker;
use App\Services\Parser\ParsedPartCollection;
use App\Services\Part\Submit\Registrar;
use App\Services\Part\TorsoShortcutMaker;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property Schema $form
 * @property ?Part $torso
 * @property ?array{id: int, name: string, description: string, parts: list<array{name: string, description: string, position: string, variations: array<string, string>}>} $template
 * @property array<int, array{label: string, used: bool}> $torsos
 * @property array<int, string> $templateOptions
 */
class TorsoShortcutHelper extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var list<string> */
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
        $this->data['parts'] = $this->defaultPartState();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Torso and Template')
                        ->key('torsoSelectStep')
                        ->schema([
                            Toggle::make('unusedTorsos')
                                ->label('Show only torsos not used by any other part')
                                ->live()
                                ->default(true),
                            Select::make('torso')
                                ->options(fn (Get $get): array => $this->torsoOptions((bool) $get('unusedTorsos')))
                                ->preload()
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set): void {
                                    unset($this->torso);
                                    if (is_null($this->torso)) {
                                        return;
                                    }
                                    $this->setNameAndDescription($set);
                                    $set('keywords', $this->maker()->defaultKeywords($this->torso));
                                    foreach (ExternalSite::cases() as $site) {
                                        $set($site->value, $this->torso->getExternalSiteNumber($site));
                                    }
                                }),
                            Select::make('templateId')
                                ->label('Template')
                                ->options($this->templateOptions)
                                ->default(fn (): ?int => Part::firstWhere('filename', 'parts/76382.dat')?->id)
                                ->selectablePlaceholder(false)
                                ->preload()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (Set $set): void {
                                    unset($this->template);
                                    $set('parts', $this->defaultPartState());
                                    $this->setNameAndDescription($set);
                                }),
                        ]),
                    Step::make('New Shortcut Details')
                        ->key('torsoEditStep')
                        ->schema([
                            TextInput::make('description')
                                ->required()
                                ->extraAttributes(['class' => 'font-mono'])
                                ->rules([
                                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                        if (Part::where('description', $value)->partsFolderOnly()->exists()) {
                                            $fail('A part with that description already exists');
                                        }
                                    },
                                ]),
                            TextInput::make('name')
                                ->required()
                                ->rules([
                                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                        $filename = 'parts/' . $this->maker()->normalizeName($value);
                                        if (Part::where('filename', $filename)->exists()) {
                                            $fail('A part with that name already exists');
                                        }
                                    },
                                ]),
                            $this->externalsSiteSection(),
                            TextInput::make('keywords')
                                ->required()
                                ->helperText('Comma separated. External site numbers are added from the fields above.')
                                ->extraAttributes(['class' => 'font-mono'])
                                ->rules(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    if (blank($get('description')) || blank($get('name'))) {
                                        return;
                                    }
                                    $header = $this->maker()->header($get('description'), $get('name'), $value, Auth::user());
                                    $errors = app(PartChecker::class)->runSingle(PatternHasSetKeyword::class, new ParsedPartCollection($header));
                                    if ($errors->isNotEmpty()) {
                                        $fail($errors->first()->message());
                                    }
                                }),
                            Section::make('Parts')
                                ->key('partFields')
                                ->columns(2)
                                ->schema(fn (): array => $this->partFields()),
                        ])
                        ->afterValidation(function (Set $set): void {
                            $fileText = $this->torsoText($this->form->getState());
                            $set('new_part', $fileText);
                            // Forced because every generated file is model.ldr, which the viewer would otherwise skip as unchanged
                            $this->dispatch('ldbi-render-model', parts: app(LDrawModelMaker::class)->webGl($fileText), force: true);
                        }),
                    Step::make('Review and Submit')
                        ->key('torsoReviewStep')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Textarea::make('new_part')
                                        ->label('Generated file')
                                        ->autosize()
                                        ->readOnly()
                                        ->extraInputAttributes(['wrap' => 'off'])
                                        ->extraAttributes(['class' => 'font-mono']),
                                    View::make('filament.forms.3d-viewer'),
                                ]),
                        ]),
                ])
                    ->submitAction(view('filament.wizard-submit-button', ['target' => 'submitFile'])),
            ])
            ->statePath('data')
            ->key('torsoWizard');
    }

    public function submitFile(): void
    {
        $this->authorize('create', Part::class);
        $data = $this->form->getState();
        $file = LDrawFile::fromArray([
            'mimetype' => 'text/plain',
            'filename' => $this->maker()->normalizeName($data['name']),
            'contents' => $this->torsoText($data),
        ]);

        $newPart = app(Registrar::class)->submit(collect([$file]), Auth::user())->first();
        $this->redirectRoute('parts.show', $newPart);
    }

    protected function maker(): TorsoShortcutMaker
    {
        return app(TorsoShortcutMaker::class);
    }

    /**
     * Fills the name and description from the selected template and torso, once both are chosen.
     */
    protected function setNameAndDescription(Set $set): void
    {
        if (is_null($this->torso) || is_null($this->template)) {
            return;
        }
        $set('description', $this->maker()->defaultDescription($this->template['description'], $this->torso));
        $set('name', $this->maker()->defaultName($this->template['name'], $this->torso));
    }

    protected function externalsSiteSection(): Fieldset
    {
        $externalSiteFields = [];
        foreach (ExternalSite::cases() as $site) {
            $otherSites = array_column(
                array_filter(ExternalSite::cases(), fn (ExternalSite $c): bool => $c !== $site),
                'value'
            );
            $externalSiteFields[] = TextInput::make($site->value)
                ->label($site->name)
                ->string()
                ->requiredWithoutAll($otherSites)
                ->extraAttributes(['class' => 'font-mono']);
        }
        return Fieldset::make('External Site Numbers')
            ->columns([
                'default' => 1,
                'md' => count(ExternalSite::cases()),
                'lg' => count(ExternalSite::cases()),
            ])
            ->schema($externalSiteFields);
    }

    /**
     * A variation and colour choice for each non-torso part of the selected template, stored under parts.{index}.
     * Built from a closure so Filament rebuilds it on render after the template changes, rather than reusing
     * the fields it cached for the previous template earlier in the same request.
     *
     * @return list<Fieldset>
     */
    protected function partFields(): array
    {
        $partFields = [];
        foreach ($this->template['parts'] ?? [] as $index => $part) {
            if ($this->maker()->isTorso($part['name'])) {
                continue;
            }
            $partOptions = [];
            if ($part['variations'] !== []) {
                $partOptions[] = Select::make('variation')
                    ->options($part['variations'])
                    ->placeholder('None (plain part)')
                    ->searchable();
            }
            $partOptions[] = LDrawColourSelect::make('color')
                ->label('Color')
                ->required();
            $name = basename($part['name'], '.dat');
            $partFields[] = Fieldset::make("{$name} - {$part['description']}")
                ->key("part{$index}")
                ->statePath("parts.{$index}")
                ->schema($partOptions);
        }

        return $partFields;
    }

    /**
     * No variation and main colour (16) for each non-torso part of the selected template.
     * Set on mount and template change, since Filament only applies field defaults when the form is first filled.
     *
     * @return array<int, array{variation: null, color: int}>
     */
    protected function defaultPartState(): array
    {
        return collect($this->template['parts'] ?? [])
            ->reject(fn (array $part): bool => $this->maker()->isTorso($part['name']))
            ->map(fn (): array => ['variation' => null, 'color' => 16])
            ->all();
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function torsoText(array $data): string
    {
        if (is_null($this->template) || is_null($this->torso)) {
            return '';
        }

        return $this->maker()->text($this->template, $data, $this->torso, Auth::user());
    }

    /**
     * @return array<int, string>
     */
    protected function torsoOptions(bool $onlyUnused): array
    {
        return collect($this->torsos)
            ->when($onlyUnused, fn ($torsos) => $torsos->reject(fn (array $torso): bool => $torso['used']))
            ->map(fn (array $torso): string => $torso['label'])
            ->all();
    }

    /**
     * Every active official 973 torso, loaded once per page and filtered in memory by the unused toggle.
     *
     * @return array<int, array{label: string, used: bool}>
     */
    #[Computed(persist: true)]
    protected function torsos(): array
    {
        return Part::select(['id', 'filename', 'description'])
            ->whereLike('filename', 'parts/973%.dat')
            ->withExists('parents')
            ->doesntHave('unofficial_part')
            ->activeParts()
            ->orderBy('filename')
            ->get()
            ->mapWithKeys(fn (Part $p): array => [$p->id => [
                'label' => "{$p->meta_name} - {$p->description}",
                'used' => (bool) $p->parents_exists,
            ]])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    #[Computed(persist: true)]
    protected function templateOptions(): array
    {
        return Part::whereIn('filename', $this->templates)
            ->orderBy('filename')
            ->get()
            ->mapWithKeys(fn (Part $p): array => [$p->id => "{$p->meta_name} - {$p->description}"])
            ->all();
    }

    #[Computed(persist: true)]
    protected function torso(): ?Part
    {
        $id = $this->data['torso'] ?? null;

        return is_null($id) ? null : Part::find($id);
    }

    /**
     * @return ?array{id: int, name: string, description: string, parts: list<array{name: string, description: string, position: string, variations: array<string, string>}>}
     */
    #[Computed(persist: true)]
    protected function template(): ?array
    {
        $id = $this->data['templateId'] ?? null;
        $part = is_null($id) ? null : Part::find($id);

        return is_null($part) ? null : $this->maker()->template($part);
    }

    #[Layout('components.layout.tracker')]
    public function render(): ViewContract
    {
        return view('livewire.torso-shortcut-helper');
    }
}
