<?php

namespace App\Livewire\Part;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use App\LDraw\Parse\Parser;
use App\Enums\CheckType;
use App\Enums\Permission;
use App\LDraw\Check\PartChecker;
use App\LDraw\LDrawFile;
use App\LDraw\PartManager;
use App\Models\Part\Part;
use App\Models\User;
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
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @property \Filament\Schemas\Schema $form
 */
class Submit extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];
    public array $part_errors = [];
    public array $submitted_parts = [];

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
                    ->multiple()
                    ->maxFiles(15)
                    ->storeFiles(false)
                    ->required()
                    ->live()
                    ->label('Files')
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get) {
                            // Check if the fileformat is text or png
                            $mimeType = $mimeType = $this->getMimeType($value);
                            if (is_null($mimeType)) {
                                $this->part_errors[] = "{$value->getClientOriginalName()}: Incorrect file type";
                                $fail('File errors');
                                return;
                            }

                            // Error check based on file type
                            if ($mimeType == 'text/plain') {
                                $part = app(Parser::class)->parse($value->get());
                                $pparts = Part::query()->byName($part->name ?? '')->get();
                                $unofficial_exists = $pparts->unofficial()->isNotEmpty();
                                $official_exists = $pparts->official()->isNotEmpty();
                                $pc = new PartChecker($part);
                                $pc->standardChecks($value->getClientOriginalName());
                                $errors = $pc->get(CheckType::holdable(), true);

                                // A part in the p and parts folder cannot have the same name
                                if (!is_null($pparts) && !is_null($part->type) && !is_null($part->name) &&
                                    $pparts->where('filename', "p/{$part->name}")->isNotEmpty() &&
                                    ($part->type == 'Part' || $part->type == 'Shortcut')) {
                                    $this->part_errors[] = "{$value->getClientOriginalName()}: " . __('partcheck.duplicate', ['type' => 'Primitive']);
                                } elseif (!is_null($pparts) && !is_null($part->type) && !is_null($part->name) &&
                                    $pparts->where('filename', "parts/{$part->name}")->isNotEmpty() &&
                                    $part->type == 'Primitive') {
                                    $this->part_errors[] = "{$value->getClientOriginalName()}: " . __('partcheck.duplicate', ['type' => 'Parts']);
                                }

                                foreach ($errors as $error) {
                                    $this->part_errors[] = "{$value->getClientOriginalName()}: {$error}";
                                }
                            } elseif ($mimeType == 'image/png') {
                                $filename = $value->getClientOriginalName();
                                $unofficial_exists = !is_null(Part::unofficial()->where('filename', 'LIKE', "%{$filename}")->first());
                                $official_exists = !is_null(Part::official()->where('filename', 'LIKE', "%{$filename}")->first());
                            }

                            // Check if the part already exists on the tracker
                            if ($unofficial_exists && $get('replace') !== true) {
                                $this->part_errors[] = "{$value->getClientOriginalName()}: " . __('partcheck.replace');
                            }

                            if ($official_exists && !$unofficial_exists && $get('official_fix') !== true) {
                                $this->part_errors[] = "{$value->getClientOriginalName()}: You must select Official File Fix to submit official part fixes";
                            }

                            if (count($this->part_errors) > 0) {
                                $fail('File errors');
                            }
                        },
                    ]),
                Toggle::make('replace')
                    ->label('Replace existing file(s)'),
                Toggle::make('official_fix')
                    ->label('Official File Fix'),
                Select::make('user_id')
                    ->relationship(name: 'user')
                    ->getOptionLabelFromRecordUsing(fn (User $u) => "{$u->realname} [{$u->name}]")
                    ->searchable()
                    ->preload()
                    ->default(Auth::user()->id)
                    ->selectablePlaceholder(false)
                    ->native(false)
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
        $manager = app(PartManager::class);
        $this->part_errors = [];
        $data = $this->form->getState();
        if (array_key_exists('user_id', $data) && Auth::user()->can(Permission::PartSubmitProxy)) {
            $user = User::find($data['user_id']);
        } else {
            $user = Auth::user();
        }
        $files = Arr::map($data['partfiles'], fn ($value, $key) => LDrawFile::fromUploadedFile($value));
        $parts = $manager->submit($files, $user, $data['comments']);

        $parts->each(function (Part $p) {
            $this->submitted_parts[] = [
                'image' => version("images/library/unofficial/" . substr($p->filename, 0, -4) . '_thumb.png'),
                'description' => $p->description,
                'filename' => $p->filename,
                'route' => route('parts.show', $p)
            ];
        });
        $data = $this->form->fill();
        $this->render();
        $this->dispatch('open-modal', id: 'post-submit');
    }

    public function postSubmit()
    {
        $this->submitted_parts = [];
        $this->dispatch('close-modal', id: 'post-submit');
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

    #[Layout('components.layout.tracker')]
    public function render(): View
    {
        return view('livewire.part.submit');
    }
}
