<?php

namespace App\Livewire\Part;

use App\Enums\CheckType;
use App\Enums\PartError;
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

    public Collection $part_messages;

    #[Locked]
    public array $submitted_parts = [];

    #[Locked]
    public ?string $rejected_files = null;

    public function mount(): void
    {
        $this->authorize('create', Part::class);
        $this->form->fill();
        $this->part_messages = collect();
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
                            $errorCount = collect($this->part_messages)
                                ->filter(fn (CheckMessageCollection $m) => $m->hasErrors())
                                ->count();

                            if ($errorCount >= count($this->data['partfiles'])) {
                                $fail('There are no files without errors');
                            }
                        },
                    ]),
                Toggle::make('replace')
                    ->label('Replace existing file(s)')
                    ->live()
                    ->afterStateUpdated(fn (?bool $state) => $state === true ? $this->removeError(PartError::ReplaceNotSelected) : $this->checkFiles()),
                Toggle::make('official_fix')
                    ->label('Official File Fix')
                    ->live()
                    ->afterStateUpdated(fn (?bool $state) => $state === true ? $this->removeError(PartError::FixNotSelected) : $this->checkFiles()),
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

    public function create(): void
    {
        $this->rejected_files = null;
        $this->submitted_parts = [];
        $manager = app(PartManager::class);
        $data = $this->form->getState();
        if (array_key_exists('user_id', $data) && Auth::user()->can(Permission::PartSubmitProxy)) {
            $user = User::find($data['user_id']);
        } else {
            $user = Auth::user();
        }
        $files = collect($data['partfiles'])
            ->reject(function (TemporaryUploadedFile $file) {
                $filename = $file->getClientOriginalName();
                return isset($this->part_errors[$filename]) || !$file->get();
            })
            ->map(fn (TemporaryUploadedFile $file) => LDrawFile::fromUploadedFile($file))
            ->values()
            ->all();
        $parts = $manager->submit($files, $user, $data['comments']);
        $partnames = $parts
            ->pluck('filename')
            ->map(fn (string $filename) => basename($filename))
            ->values()
            ->all();
        $this->rejected_files = collect($data['partfiles'])
            ->map(fn (TemporaryUploadedFile $file) => $file->getClientOriginalName())
            ->reject(fn (string $file) => in_array($file, $partnames))
            ->implode(', ');
        $this->submitted_parts = $parts
            ->map(fn (Part $p) => [
                    'image' => $p->getFirstMediaUrl('image', 'thumb'),
                    'description' => $p->description,
                    'filename' => $p->filename,
                    'route' => route('parts.show', $p)
                ]
            )
            ->values()
            ->all();
        $this->form->fill();
        $this->part_errors = collect();
        $this->dispatch('open-modal', id: 'post-submit');
    }

    public function getMimeType(TemporaryUploadedFile $file): ?string
    {
        $path = $file->getPath() . '/' . $file->getFilename();

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($finfo, $path);

        if ($type === 'text/plain') {
            return $type;
        }

        if ($type === 'image/png') {
            return @imagecreatefrompng($path) ? $type : null;
        }

        return $type;
    }

    protected function removeError(PartError $error): void
    {
        foreach ($this->part_messages as $file => $messages) {

            $filtered = $messages
                ->reject(fn (CheckMessage $m) => $m->error === $error);

            if ($filtered->isEmpty()) {
                unset($this->part_messages[$file]);
                $this->dispatch('setFileState', state: true, filename: $file);
                continue;
            }

            $this->part_messages[$file] = $filtered;
        }
    }

    public function removeFile(string $filename): void
    {
        unset($this->part_messages[$filename]);
    }

    public function checkFiles(): void
    {
        collect($this->data['partfiles'])
            ->each(fn ($file) => $this->checkFile($file->getClientOriginalName()));
    }

    public function checkFile(string $filename, ?string $fileId = null): void
    {
        $file = collect($this->data['partfiles'])
            ->first(fn ($f) => $f->getClientOriginalName() === $filename);

        if (!$file) {
            return;
        }

        $filename = $file->getClientOriginalName();
        $mimeType = $this->getMimeType($file);
        $checkmessages = new CheckMessageCollection();
        $unofficialExists = false;
        $officialExists = false;

        if ($mimeType === 'text/plain') {

            $content = $file->get();
            $part = new ParsedPartCollection($content);

            $partname = $part->name() ?? '';
            $parts = Part::query()->byName($partname)->get();

            $unofficialExists = $parts->unofficial()->isNotEmpty();
            $officialExists   = $parts->official()->isNotEmpty();

            $checkmessages = app(PartChecker::class)->run($part, $filename);

            if ($part->type() === PartType::Primitive || $part->type()?->inPartsFolder()) {
                $folder = $part->type() === PartType::Primitive ? 'parts/' : 'p/';

                if ($parts->where('filename', "{$folder}{$partname}")->isNotEmpty()) {
                    $checkmessages->push(CheckMessage::fromArray([
                        'checkType' => CheckType::Error,
                        'error' => PartError::DuplicateFile,
                        'value' => $part->type() === PartType::Primitive ? 'Parts' : 'Primitive',
                    ]));
                }
            }
        }

        elseif ($mimeType === 'image/png') {

            $parts = Part::query()
                ->whereLike('filename', "%{$filename}")
                ->get();

            $unofficialExists = $parts->unofficial()->isNotEmpty();
            $officialExists   = $parts->official()->isNotEmpty();
        }

        else {
            $checkmessages->push(
                CheckMessage::fromPartError(PartError::InvalidFileFormat)
            );
        }

        if ($unofficialExists && !($this->data['replace'] ?? false)) {
            $checkmessages->push(CheckMessage::fromPartError(PartError::ReplaceNotSelected));
        }

        if ($officialExists && !$unofficialExists && !($this->data['official_fix'] ?? false)) {
            $checkmessages->push(CheckMessage::fromPartError(PartError::FixNotSelected));
        }

        // --- Store result ---
        if ($checkmessages->isNotEmpty()) {
            $this->part_messages[$filename] = $checkmessages;
            $this->dispatch('setFileState', state: false, filename: $filename, fileId: $fileId);
            return;
        }

        // Success path (optional but cleaner symmetry)
        unset($this->part_messages[$filename]);
        $this->dispatch('setFileState', state: true, filename: $filename, fileId: $fileId);
    }

    #[Layout('components.layout.tracker')]
    public function render(): View
    {
        return view('livewire.part.submit');
    }
}
