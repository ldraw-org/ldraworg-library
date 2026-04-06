<?php

namespace App\Livewire\Part;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Submit\SubmitFileValidator;
use Filament\Schemas\Schema;
use App\Enums\PartType;
use App\Enums\Permission;
use App\Services\LDraw\LDrawFile;
use App\Services\LDraw\Managers\Part\PartManager;
use App\Models\Part\Part;
use App\Models\User;
use App\Services\Check\CheckMessage;
use App\Services\Check\CheckMessageCollection;
use App\Services\Check\PartChecker;
use App\Services\Parser\ParsedPartCollection;
use Closure;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @property \Filament\Schemas\Schema $form
 */
class Submit extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];

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
                    ->required()
                    ->rules([
                        fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            $totalFiles = count($this->data['partfiles'] ?? []);

                            if ($totalFiles === 0) {
                                return;
                            }

                            $filesWithErrors = collect($this->fileStates)
                                ->filter(fn ($state) => $state['hasErrors'] ?? false)
                                ->count();

                            if ($filesWithErrors >= $totalFiles) {
                                $fail('There are no files without errors');
                            }
                        },
                    ]),
                Toggle::make('replace')
                    ->label('Replace existing file(s)')
                    ->live()
                    ->afterStateUpdated(function (?bool $state) {
                        if ($state) {
                            $this->removeErrorFromAllFiles(PartError::ReplaceNotSelected);
                        } else {
                            $this->checkFiles();
                        }
                    }),
                Toggle::make('official_fix')
                    ->label('Official File Fix')
                    ->live()
                    ->afterStateUpdated(function (?bool $state) {
                        if ($state) {
                            $this->removeErrorFromAllFiles(PartError::FixNotSelected);
                        } else {
                            $this->checkFiles();
                        }
                    }),
              Select::make('user_id')
                    ->relationship(name: 'user')
                    ->getOptionLabelFromRecordUsing(fn (User $u) => "{$u->realname} [{$u->name}]")
                    ->searchable()
                    ->preload()
                    ->default(Auth::user()->id)
                    ->selectablePlaceholder(false)
                    ->label('Proxy User')
                    ->visible(Auth::user()->can(Permission::PartSubmitProxy)),
                Textarea::make('comments')
                    ->rows(5)
                    ->nullable()
                    ->string()
            ])
            ->statePath('data')
            ->model(Part::class);
    }

    protected function findUploadedFile(string $filename): ?TemporaryUploadedFile
    {
        return collect($this->data['partfiles'])
            ->first(fn ($file) =>
                $file->getClientOriginalName() === $filename
            );
    }

    protected function storeFileValidationState(string $filename, CheckMessageCollection $collection): void
    {
        // store back as array (Livewire-friendly)
        $this->fileStates[$filename]['messages'] = $collection->map->toArray()->all();

        // recalc derived flags
        $this->fileStates[$filename]['hasErrors'] = $collection->hasErrors();
        $this->fileStates[$filename]['hasWarnings'] = $collection->hasWarnings();
        $this->fileStates[$filename]['hasTrackerHolds'] = $collection->hasTrackerHolds();
        // notify frontend
        $this->dispatch('setFileState',
            state: ! $collection->hasErrors(),
            filename: $filename
        );
    }

    public function checkFile(string $filename, SubmitFileValidator $submitFileValidator): void
    {
        $uploaded = $this->findUploadedFile($filename);

        if (! $uploaded) {
            return;
        }

        $ldrawFile = LDrawFile::fromUploadedFile($uploaded);

        $messages = $submitFileValidator->validate(
            file: $ldrawFile,
            replace: $this->data['replace'] ?? false,
            officialFix: $this->data['official_fix'] ?? false,
        );

        $this->storeFileValidationState($filename, $messages);
    }

    protected function checkFiles(): void
    {
        foreach ($this->data['partfiles'] as $file) {
            $this->checkFile($file, app(SubmitFileValidator::class));
        }
    }

    protected function removeErrorFromFile(
        string $filename,
        PartError $error
    ): void {

        if (! isset($this->fileStates[$filename])) {
            return;
        }

        $collection = CheckMessageCollection::fromArray(
            $this->fileStates[$filename]['messages']
        );

        $collection = $collection
            ->reject(fn (CheckMessage $message) =>
                $message->error === $error
            );

        $this->storeFileValidationState(
            $filename,
            $collection
        );
    }

    protected function removeErrorFromAllFiles(
        PartError $error
    ): void {

        foreach (array_keys($this->fileStates) as $filename) {

            $this->removeErrorFromFile(
                $filename,
                $error
            );
        }
    }

    public function create(): void
    {
        $this->rejected_files = null;
        $this->submitted_parts = [];
        $manager = app(PartManager::class);
        $data = $this->form->getState();

        if (
            array_key_exists('user_id', $data)
            && Auth::user()->can(Permission::PartSubmitProxy)
        ) {
            $user = User::find($data['user_id']);
        } else {
            $user = Auth::user();
        }

        $files = collect($data['partfiles'] ?? [])
            ->reject(function (TemporaryUploadedFile $file) {

                $filename = $file->getClientOriginalName();

                return
                    ! isset($this->fileStates[$filename])
                    || ($this->fileStates[$filename]['hasErrors'] ?? true)
                    || ! $file->get();
            })
            ->map(fn (TemporaryUploadedFile $file) =>
            LDrawFile::fromUploadedFile($file)
            )
            ->values()
            ->all();

        $parts = $manager->submit($files, $user, $data['comments']);

        $submittedNames = $parts
            ->pluck('filename')
            ->map(fn (string $filename) =>
            basename($filename)
            )
            ->values()
            ->all();

        $this->rejected_files = collect($data['partfiles'] ?? [])
            ->map(fn (TemporaryUploadedFile $file) =>
            $file->getClientOriginalName()
            )
            ->reject(fn (string $filename) =>
            in_array($filename, $submittedNames)
            )
            ->implode(', ');

        $this->submitted_parts = $parts
            ->map(fn (Part $p) => [
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
        $this->fileStates = collect($this->fileStates)
            ->forget($filename)
            ->all();
    }

    #[Layout('components.layout.tracker')]
    public function render(): View
    {
        return view('livewire.part.submit');
    }
}
