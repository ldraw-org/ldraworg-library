<?php

namespace App\Livewire\Part;

use App\Models\Part\Part;
use App\Services\Check\CheckMessage;
use App\Services\Check\CheckMessageCollection;
use App\Services\Check\Enums\PartError;
use App\Services\LDraw\LDrawFile;
use App\Services\Part\Submit\Registrar;
use App\Services\Part\Submit\Validator;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Livewire\Partials\PartialsComponentHook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Submit extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];

    /**
     * Validation results for every uploaded file, keyed by the upload's key (the FilePond item's serverId).
     * Display only: create() re-validates every file.
     *
     * @var array<string, array{filename: string, name: ?string, messages: CheckMessageCollection, hasErrors: bool, hasWarnings: bool, hasTrackerHolds: bool}>
     */
    #[Locked]
    public array $fileStates = [];

    #[Locked]
    public array $submitted_parts = [];

    #[Locked]
    public ?string $rejected_files = null;

    public function mount(): void
    {
        $this->authorize('create', Part::class);
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('partfiles')
                    ->label('Files')
                    ->multiple()
                    ->maxFiles(15)
                    // KB; the largest library file is about 1.9 MB
                    ->maxSize(5120)
                    ->storeFiles(false)
                    ->previewable(false)
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn () => $this->syncFileStates()),
                Toggle::make('replace')
                    ->label('Replace existing file(s)')
                    ->live()
                    ->afterStateUpdated(function (?bool $state): void {
                        if ($state) {
                            $this->removeErrorFromAllFiles(PartError::ReplaceNotSelected);
                        } else {
                            $this->checkFiles();
                        }
                    }),
                Toggle::make('official_fix')
                    ->label('Official File Fix')
                    ->live()
                    ->afterStateUpdated(function (?bool $state): void {
                        if ($state) {
                            $this->removeErrorFromAllFiles(PartError::FixNotSelected);
                        } else {
                            $this->checkFiles();
                        }
                    }),
                Textarea::make('comments')
                    ->rows(5)
                    ->nullable(),
            ])
            ->statePath('data');
    }

    /**
     * Files whose validation produced messages, for the results panel.
     *
     * @return array<string, array{filename: string, name: ?string, messages: CheckMessageCollection, hasErrors: bool, hasWarnings: bool, hasTrackerHolds: bool}>
     */
    #[Computed]
    public function filesWithMessages(): array
    {
        return array_filter($this->fileStates, fn (array $state): bool => $state['messages']->isNotEmpty());
    }

    /**
     * @return array<string, TemporaryUploadedFile>
     */
    protected function uploadedFiles(): array
    {
        return array_filter(
            $this->data['partfiles'] ?? [],
            fn (mixed $file): bool => $file instanceof TemporaryUploadedFile
        );
    }

    /**
     * Validates newly uploaded files and forgets removed ones. Runs when Filament finishes or removes an upload.
     */
    protected function syncFileStates(): void
    {
        $uploads = $this->uploadedFiles();

        if (array_diff_key($this->fileStates, $uploads) !== []) {
            // Filament removes uploads through a renderless call, so the results panel would otherwise go stale
            app(PartialsComponentHook::class)->forceRender($this);
        }

        $this->fileStates = array_intersect_key($this->fileStates, $uploads);
        $submitFileValidator = app(Validator::class);

        foreach (array_diff_key($uploads, $this->fileStates) as $key => $upload) {
            $this->validateAndStoreFile($key, LDrawFile::fromUploadedFile($upload), $submitFileValidator);
        }

        $this->flagDuplicateNames();
    }

    protected function checkFiles(): void
    {
        $this->fileStates = [];
        $this->syncFileStates();
    }

    protected function validateAndStoreFile(int|string $key, LDrawFile $file, Validator $submitFileValidator): void
    {
        $messages = $submitFileValidator->validate(
            file: $file,
            replace: (bool) ($this->data['replace'] ?? false),
            officialFix: (bool) ($this->data['official_fix'] ?? false),
        );

        $this->storeFileValidationState($key, $file->filename, $submitFileValidator->submittedName($file), $messages);
    }

    /**
     * Rejects every file that would be saved under the same name as another file in the submission.
     */
    protected function flagDuplicateNames(): void
    {
        $duplicateNames = collect($this->fileStates)->pluck('name')->filter()->duplicates()->unique()->all();

        foreach ($this->fileStates as $key => $state) {
            $messages = $state['messages']->reject(fn (CheckMessage $message): bool => $message->check === PartError::DuplicateInSubmission);

            if (in_array($state['name'], $duplicateNames, true)) {
                $messages->push(CheckMessage::fromArray(['check' => PartError::DuplicateInSubmission, 'value' => $state['name']]));
            }

            $this->storeFileValidationState($key, $state['filename'], $state['name'], $messages);
        }
    }

    protected function storeFileValidationState(int|string $key, string $filename, ?string $name, CheckMessageCollection $messages): void
    {
        $this->fileStates[$key] = [
            'filename' => $filename,
            'name' => $name,
            'messages' => $messages,
            'hasErrors' => $messages->hasErrors(),
            'hasWarnings' => $messages->hasWarnings(),
            'hasTrackerHolds' => $messages->hasTrackerHolds(),
        ];
    }

    protected function removeErrorFromAllFiles(PartError $error): void
    {
        foreach ($this->fileStates as $key => $state) {
            $this->storeFileValidationState(
                $key,
                $state['filename'],
                $state['name'],
                $state['messages']->reject(fn (CheckMessage $message): bool => $message->check === $error)
            );
        }
    }

    public function create(Validator $submitFileValidator, Registrar $registrar): void
    {
        // The page may have been open since before the user lost the right to submit
        $this->authorize('create', Part::class);

        $this->rejected_files = null;
        $this->submitted_parts = [];
        $this->fileStates = [];
        $data = $this->form->getState();

        /** @var Collection<string, LDrawFile> $files */
        $files = collect($data['partfiles'])
            ->map(fn (TemporaryUploadedFile $file): LDrawFile => LDrawFile::fromUploadedFile($file));

        $files->each(fn (LDrawFile $file, int|string $key) => $this->validateAndStoreFile($key, $file, $submitFileValidator));
        $this->flagDuplicateNames();

        $accepted = $files->reject(fn (LDrawFile $file, int|string $key): bool => $this->fileStates[$key]['hasErrors']);

        if ($accepted->isEmpty()) {
            $this->addError('data.partfiles', 'There are no files without errors');

            return;
        }

        $parts = $registrar->submit($accepted->values(), Auth::user(), $data['comments'] ?? null)->load('media');

        $this->rejected_files = $files
            ->diffKeys($accepted)
            ->map(fn (LDrawFile $file): string => $file->filename)
            ->implode(', ');

        $this->submitted_parts = $parts
            ->map(fn (Part $p): array => [
                'image' => $p->getFirstMediaUrl('image', 'thumb'),
                'description' => $p->description,
                'filename' => $p->filename,
                'route' => route('parts.show', $p),
            ])
            ->values()
            ->all();

        $this->form->fill();
        $this->fileStates = [];
        $this->dispatch('open-modal', id: 'post-submit');
    }

    #[Layout('components.layout.tracker')]
    public function render(): View
    {
        return view('livewire.part.submit');
    }
}
