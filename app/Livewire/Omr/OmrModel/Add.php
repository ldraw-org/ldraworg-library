<?php

namespace App\Livewire\Omr\OmrModel;

use App\Filament\Forms\Components\AuthorSelect;
use App\Models\Omr\OmrModel;
use App\Models\Omr\Set;
use App\Models\User;
use App\Services\LDraw\LDrawFile;
use App\Services\LDraw\LDrawModelMaker;
use App\Services\LDraw\Managers\OmrModelManager;
use App\Services\Omr\Set\Writer as SetWriter;
use App\Services\OmrModel\UploadChecker;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Add extends Component implements HasSchemas
{
    use InteractsWithSchemas;
    use RestrictsFileUploadsToSchemaComponents;

    public ?array $data = [];

    /**
     * @var array{errors?: list<string>, warnings?: list<string>}
     */
    #[Locked]
    public array $checks = [];

    public function mount(): void
    {
        $this->authorize('create', OmrModel::class);
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('modelfile')
                    ->label('Model File')
                    ->storeFiles(false)
                    ->previewable(false)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->checkModelFile())
                    ->rules([
                        fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            if (($this->checks['errors'] ?? []) !== []) {
                                $fail('The model file has errors');
                            }
                        },
                    ]),
                AuthorSelect::make()
                    ->required(),
                Select::make('set_id')
                    ->label('Set')
                    ->options(fn () => Set::pluck('number', 'id'))
                    ->searchable()
                    ->required()
                    ->live()
                    ->createOptionForm([
                        TextInput::make('number')
                            ->required()
                            ->rules([
                                fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                    $value = $this->withSetVersion($value);
                                    if (Set::where('number', $value)->exists()) {
                                        $fail('Set already exists');
                                        return;
                                    }
                                    if (is_null(app(SetWriter::class)->setData($value))) {
                                        $fail('Set number not found at Rebrickable');
                                    }
                                },
                            ]),
                    ])
                    ->createOptionUsing(
                        fn (array $data): int => app(SetWriter::class)->findFromSetNumber($this->withSetVersion($data['number']), createIfMissing: true)?->id
                            ?? throw ValidationException::withMessages(['number' => 'Unable to add the set from Rebrickable'])
                    ),
                Toggle::make('alt_model')
                    ->label('Alternate Model')
                    ->live()
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                            if (!$value && OmrModel::where('set_id', $get('set_id'))->whereNull('alt_model_name')->exists()) {
                                $fail('There is already a main model for this set');
                            }
                        },
                    ]),
                TextInput::make('alt_model_name')
                    ->label('Alternate Model Name')
                    ->requiredIfAccepted('alt_model')
                    ->visible(fn (Get $get): bool => (bool) $get('alt_model'))
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                            if (OmrModel::where('set_id', $get('set_id'))->where('alt_model_name', $value)->exists()) {
                                $fail('There is already an alt model with that name');
                            }
                        },
                    ]),
                Toggle::make('missing_parts'),
                Toggle::make('missing_patterns'),
                Toggle::make('missing_stickers'),
                Textarea::make('notes')
                    ->rows(4)
                    ->nullable(),
            ])
            ->statePath('data');
    }

    /**
     * Checks the uploaded file and fills in what it can work out from it, as the part submit form does on upload.
     */
    protected function checkModelFile(): void
    {
        $file = $this->uploadedFile();
        if (is_null($file)) {
            $this->checks = [];
            return;
        }

        $result = app(UploadChecker::class)->check(LDrawFile::fromUploadedFile($file));
        $this->checks = ['errors' => $result['errors'], 'warnings' => $result['warnings']];

        if ($result['errors'] !== []) {
            return;
        }

        $this->data['set_id'] = $result['set_id'] ?? $this->data['set_id'] ?? null;
        $this->data['user_id'] = $result['user_id'] ?? $this->data['user_id'] ?? null;
        $this->data['alt_model'] = $result['alt_model'];
        $this->data['missing_parts'] = $result['missing_parts'];
    }

    protected function uploadedFile(): ?TemporaryUploadedFile
    {
        $file = collect($this->data['modelfile'] ?? [])->first();

        return $file instanceof TemporaryUploadedFile ? $file : null;
    }

    protected function withSetVersion(string $number): string
    {
        return Str::endsWith($number, ['-1', '-2']) ? $number : "{$number}-1";
    }

    public function preview(LDrawModelMaker $maker): void
    {
        $file = $this->uploadedFile();
        if (is_null($file) || ($this->checks['errors'] ?? []) !== []) {
            return;
        }

        $this->dispatch('open-modal', id: 'ldbi');
        $this->dispatch(
            'ldbi-render-model',
            modelType: 'user',
            mainModelName: 'model.ldr',
            parts: $maker->webGl($file->get()),
            force: true,
        );
    }

    public function create(OmrModelManager $manager): void
    {
        $this->authorize('create', OmrModel::class);
        $data = $this->form->getState();

        /** @var TemporaryUploadedFile $file */
        $file = $data['modelfile'];
        $set = Set::findOrFail($data['set_id']);
        $model = $manager->addModel($file->get(), $set, User::findOrFail($data['user_id']), $data);

        Notification::make()
            ->title("Added {$model->filename()} to {$set->number} - {$set->name}")
            ->actions([
                Action::make('view')
                    ->url(route('omr.sets.show', $set)),
            ])
            ->success()
            ->send();

        $this->checks = [];
        $this->form->fill();
    }

    #[Layout('components.layout.omr')]
    public function render(): View
    {
        return view('livewire.omr.omr-model.add');
    }
}
