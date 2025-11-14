<?php

namespace App\Livewire\Part;

use App\Enums\CheckType;
use App\Enums\PartError;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
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
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Session;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @property \Filament\Schemas\Schema $form
 */
class Submit extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];

    #[Locked]
    public array $part_errors = [];
  
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
                            if (count($this->part_errors) >= count($this->data['partfiles'])) {
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
            ->reject(fn (TemporaryUploadedFile $file) => array_key_exists($file->getClientOriginalName(), $this->part_errors))
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
        $this->part_errors = [];
        $this->dispatch('open-modal', id: 'post-submit');
    }

    public function getMimeType(TemporaryUploadedFile $file): ?string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($finfo, $file->getPath() . '/' . $file->getFilename());
        switch ($type) {
            case 'text/plain':
                break;
            case 'image/png':
                if (!$img = @imagecreatefrompng($file->getPath() . '/' . $file->getFilename())) {
                    $type = null;
                }
                break;
            default:
                $type = null;
        }

        return $type;
    }
    
    protected function removeError(PartError $error): void
    {
        foreach ($this->part_errors as $file => &$errors) {
            $errors = collect($errors)
                ->reject(fn (CheckMessage $message) => $message->error == $error);
            if ($errors->isEmpty()) {
                unset($this->part_errors[$file]);
                $this->dispatch('passFile', $file);
            }
            $errors = $errors->values()->all();
        }
    }

    public function removeFile(string $filename): void
    {
        unset($this->part_errors[$filename]);
    }

    public function checkFiles(): void
    {
        foreach($this->data['partfiles'] as $file)
        {
            $this->checkFile($file->getClientOriginalName());
        }
    }
  
    public function checkFile(string $filename): void
    {
        foreach($this->data['partfiles'] as $file) {
            if ($file->getClientOriginalName() == $filename) {
                break;
            }
        }
        $errors = new CheckMessageCollection();
        $mimeType = $mimeType = $this->getMimeType($file);
        switch($mimeType) {
            case 'text/plain':
                $part = new ParsedPartCollection($file->get());

                $partname = $part->name() ?? '';
                $pparts = Part::query()->byName($partname)->get();
                $unofficial_exists = $pparts->unofficial()->isNotEmpty();
                $official_exists = $pparts->official()->isNotEmpty();
                $pc = app(PartChecker::class);
                $errors = $errors->merge($pc->run($part, $file->getClientOriginalName()));
                
                if ($part->type() == PartType::Primitive || $part->type()?->inPartsFolder()) {
                    $searchFolder = $part->type() == PartType::Primitive ? 'parts/' : 'p/';
                    if ($pparts->where('filename', "{$searchFolder}{$partname}")->isNotEmpty()) {
                        $errors->push(CheckMessage::fromArray([
                            'checkType' => CheckType::Error,
                            'error' => PartError::DuplicateFile,
                            'value' => $part->type() == PartType::Primitive ? 'Parts' : 'Primitive',
                        ]));
                    }
                }
                break;
            case 'image/png':
                $filename = $file->getClientOriginalName();
                $unofficial_exists = !is_null(Part::unofficial()->whereLike('filename', "%{$filename}")->first());
                $official_exists = !is_null(Part::official()->whereLike('filename', "%{$filename}")->first());
                break;
            default:
                $errors->push(CheckMessage::fromPartError(PartError::InvalidFileFormat));
                $this->part_errors[$file->getClientOriginalName()] = $errors->values()->all();
                $this->dispatch('failFile', $filename);
                return;
        }
            // Check if the part already exists on the tracker
        if ($unofficial_exists && $this->data['replace'] !== true) {
            $errors->push(CheckMessage::fromPartError(PartError::ReplaceNotSelected));
        }

        if ($official_exists && !$unofficial_exists && $this->data['official_fix'] !== true) {
            $errors->push(CheckMessage::fromPartError(PartError::FixNotSelected));
        }

        if ($errors->isNotEmpty()) {
            $this->part_errors[$file->getClientOriginalName()] = $errors->values()->all();
            $this->dispatch('failFile', $filename);
        }
    }
  
    #[Layout('components.layout.tracker')]
    public function render(): View
    {
        return view('livewire.part.submit');
    }
}
