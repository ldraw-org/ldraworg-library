<?php

namespace App\Livewire\Omr\OmrModel;

use App\Filament\Forms\Components\AuthorSelect;
use App\Filament\Traits\HasOmrModelFormFields;
use App\Models\Omr\OmrModel;
use App\Models\Omr\Set;
use App\Models\User;
use App\Services\LDraw\LDrawModelMaker;
use App\Services\Omr\OmrModel\Creator;
use App\Services\Omr\Set\Writer as SetWriter;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Add extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use RestrictsFileUploadsToSchemaComponents;
    use HasOmrModelFormFields;

    public ?array $data = [];

    public function mount(): void
    {
        $this->authorize('create', OmrModel::class);
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->modelFileField(),
                $this->modelChecksView(),
                AuthorSelect::make()
                    ->required(),
                Select::make('set_id')
                    ->label('Set')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Set::query()
                        ->whereLike('number', "{$search}%")
                        ->orWhereLike('name', "%{$search}%")
                        ->orderBy('number')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Set $set): array => [$set->id => $this->setLabel($set)])
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => ($set = Set::find($value)) ? $this->setLabel($set) : null)
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
                ...$this->modelDetailFields(),
            ])
            ->statePath('data');
    }

    protected function setLabel(Set $set): string
    {
        return "{$set->number} - {$set->name}";
    }

    protected function withSetVersion(string $number): string
    {
        return Str::endsWith($number, ['-1', '-2']) ? $number : "{$number}-1";
    }

    public function preview(LDrawModelMaker $maker): void
    {
        $file = $this->uploadedFile($this->data['modelfile'] ?? null);
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

    public function create(Creator $creator): void
    {
        $this->authorize('create', OmrModel::class);
        $data = $this->form->getState();

        /** @var TemporaryUploadedFile $file */
        $file = $data['modelfile'];
        $set = Set::findOrFail($data['set_id']);
        $model = $creator->create($file->get(), $set, User::findOrFail($data['user_id']), $data);

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
