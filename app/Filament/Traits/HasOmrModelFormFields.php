<?php

namespace App\Filament\Traits;

use App\Models\Omr\OmrModel;
use App\Services\LDraw\LDrawFile;
use App\Services\Omr\OmrModel\UploadChecker;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Form fields shared by the OMR model add page and the model edit action.
 */
trait HasOmrModelFormFields
{
    /**
     * @var array{errors?: list<string>, warnings?: list<string>}
     */
    #[Locked]
    public array $checks = [];

    /**
     * The model file upload, which checks the file and fills in what it can from it, as the part submit form does on upload.
     * A file replacing an existing model's file is optional and does not change the model's set.
     */
    protected function modelFileField(?OmrModel $replacing = null): FileUpload
    {
        return FileUpload::make('modelfile')
            ->label(is_null($replacing) ? 'Model File' : 'New Model File')
            ->helperText(is_null($replacing) ? null : 'Leave empty to keep the current file')
            ->storeFiles(false)
            ->previewable(false)
            ->required(is_null($replacing))
            ->live()
            ->afterStateUpdated(fn (mixed $state, Get $get, Set $set) => $this->checkModelFile($this->uploadedFile($state), $get, $set, $replacing))
            ->rules([
                fn (): Closure => function (string $attribute, $value, Closure $fail) {
                    if (($this->checks['errors'] ?? []) !== []) {
                        $fail('The model file has errors');
                    }
                },
            ]);
    }

    protected function modelChecksView(): View
    {
        return View::make('filament.forms.omr-model-checks')
            ->viewData(fn (): array => ['checks' => $this->checks]);
    }

    /**
     * @return list<Component|Toggle|TextInput|Textarea>
     */
    protected function modelDetailFields(?OmrModel $editing = null): array
    {
        $otherSetModels = fn (Get $get): Builder => OmrModel::query()
            ->where('set_id', $editing->set_id ?? $get('set_id'))
            ->when($editing, fn (Builder $query) => $query->whereKeyNot($editing->id));

        return [
            Toggle::make('alt_model')
                ->label('Alternate Model')
                ->live()
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get, $otherSetModels) {
                        if (!$value && $otherSetModels($get)->whereNull('alt_model_name')->exists()) {
                            $fail('There is already a main model for this set');
                        }
                    },
                ]),
            TextInput::make('alt_model_name')
                ->label('Alternate Model Name')
                ->requiredIfAccepted('alt_model')
                ->visible(fn (Get $get): bool => (bool) $get('alt_model'))
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get, $otherSetModels) {
                        if ($otherSetModels($get)->where('alt_model_name', $value)->exists()) {
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
        ];
    }

    protected function checkModelFile(?TemporaryUploadedFile $file, Get $get, Set $set, ?OmrModel $replacing): void
    {
        if (is_null($file)) {
            $this->checks = [];
            return;
        }

        $result = app(UploadChecker::class)->check(LDrawFile::fromUploadedFile($file), $replacing);
        $this->checks = ['errors' => $result['errors'], 'warnings' => $result['warnings']];

        if ($result['errors'] !== []) {
            return;
        }

        $set('user_id', $result['user_id'] ?? $get('user_id'));
        $set('missing_parts', $result['missing_parts']);
        if (is_null($replacing)) {
            $set('set_id', $result['set_id'] ?? $get('set_id'));
            $set('alt_model', $result['alt_model']);
        }
    }

    protected function uploadedFile(mixed $state): ?TemporaryUploadedFile
    {
        $file = collect(Arr::wrap($state))->first();

        return $file instanceof TemporaryUploadedFile ? $file : null;
    }
}
