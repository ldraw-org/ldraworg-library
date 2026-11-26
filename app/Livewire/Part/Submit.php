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
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Submit extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];

    /**
     * Validation results shown to the user, keyed by uploaded filename. Display only: create() re-validates every file.
     *
     * @var array<string, array{messages: CheckMessageCollection, hasErrors: bool, hasWarnings: bool, hasTrackerHolds: bool}>
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
                    ->storeFiles(false)
                    ->previewable(false)
                    ->live()
                    ->required(),
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

    protected function findUploadedFile(string $filename): ?TemporaryUploadedFile
    {
        /** @var Collection<int, TemporaryUploadedFile> $files */
        $files = collect($this->data['partfiles'] ?? []);

        return $files->first(
            fn (TemporaryUploadedFile $file): bool => $file->getClientOriginalName() === $filename
        );
    }

    protected function validateFile(LDrawFile $file, Validator $submitFileValidator): CheckMessageCollection
    {
        return $submitFileValidator->validate(
            file: $file,
            replace: (bool) ($this->data['replace'] ?? false),
            officialFix: (bool) ($this->data['official_fix'] ?? false),
        );
    }

    protected function storeFileValidationState(string $filename, CheckMessageCollection $collection): void
    {
        if ($collection->isEmpty()) {
            $this->removeFile($filename);
            $this->dispatch(
                'setFileState',
                state: true,
                filename: $filename
            );

            return;
        }

        $this->fileStates[$filename] = [
            'messages' => $collection,
            'hasErrors' => $collection->hasErrors(),
            'hasWarnings' => $collection->hasWarnings(),
            'hasTrackerHolds' => $collection->hasTrackerHolds(),
        ];

        $this->dispatch(
            'setFileState',
            state: ! $collection->hasErrors(),
            filename: $filename
        );
    }

    public function checkFile(string $filename, Validator $submitFileValidator): void
    {
        $uploaded = $this->findUploadedFile($filename);

        if (! $uploaded) {
            return;
        }

        $this->storeFileValidationState(
            $filename,
            $this->validateFile(LDrawFile::fromUploadedFile($uploaded), $submitFileValidator)
        );
    }

    protected function checkFiles(): void
    {
        $submitFileValidator = app(Validator::class);

        foreach ($this->data['partfiles'] ?? [] as $file) {
            $this->checkFile($file->getClientOriginalName(), $submitFileValidator);
        }
    }

    protected function removeErrorFromFile(string $filename, PartError $error): void
    {
        if (! isset($this->fileStates[$filename])) {
            return;
        }

        $collection = $this->fileStates[$filename]['messages']
            ->reject(fn (CheckMessage $message): bool => $message->check === $error);

        $this->storeFileValidationState($filename, $collection);
    }

    protected function removeErrorFromAllFiles(PartError $error): void
    {
        foreach (array_keys($this->fileStates) as $filename) {
            $this->removeErrorFromFile($filename, $error);
        }
    }

    public function create(Validator $submitFileValidator, Registrar $registrar): void
    {
        $this->rejected_files = null;
        $this->submitted_parts = [];
        $this->fileStates = [];
        $data = $this->form->getState();

        /** @var Collection<string, LDrawFile> $files */
        $files = collect($data['partfiles'])
            ->mapWithKeys(fn (TemporaryUploadedFile $file): array => [
                $file->getClientOriginalName() => LDrawFile::fromUploadedFile($file),
            ]);

        $accepted = $files->filter(function (LDrawFile $file, string $filename) use ($submitFileValidator): bool {
            $messages = $this->validateFile($file, $submitFileValidator);
            $this->storeFileValidationState($filename, $messages);

            return ! $messages->hasErrors();
        });

        if ($accepted->isEmpty()) {
            $this->addError('data.partfiles', 'There are no files without errors');

            return;
        }

        $parts = $registrar->submit($accepted->values(), Auth::user(), $data['comments'] ?? null)->load('media');

        $this->rejected_files = $files->keys()->diff($accepted->keys())->implode(', ');

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

    public function removeFile(string $filename): void
    {
        unset($this->fileStates[$filename]);
    }

    #[Layout('components.layout.tracker')]
    public function render(): View
    {
        return view('livewire.part.submit');
    }
}
