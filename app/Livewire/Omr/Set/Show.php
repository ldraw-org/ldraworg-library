<?php

namespace App\Livewire\Omr\Set;

use App\Enums\LibraryIcon;
use App\Filament\Forms\Components\AuthorSelect;
use App\Filament\Traits\HasOmrModelFormFields;
use App\Models\Omr\OmrModel;
use App\Models\Omr\Set;
use App\Models\User;
use App\Services\LDraw\Managers\OmrModelManager;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Show extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use RestrictsFileUploadsToSchemaComponents;
    use HasOmrModelFormFields;

    public Set $set;
    public ?string $model_name = null;
    public ?int $model_id = null;

    public function mount(?Set $set, ?Set $setnumber)
    {
        if (!is_null($set) && $set->exists) {
            $this->set = $set;
        } elseif (!is_null($setnumber) && $setnumber->exists) {
            $this->set = $setnumber;
        } else {
            return response('404');
        }
    }

    public function openModal(int $id): void
    {
        if ($id != $this->model_id) {
            $this->model_name = $this->set->models->find($id)->filename();
            $this->model_id = $id;
        }
        $this->dispatch('open-modal', id: 'ldbi');
        $this->dispatch('ldbi-render-model', modelId: $this->model_id, mainModelName: $this->model_name);
    }

    public function editModelAction(): Action
    {
        return Action::make('editModel')
            ->label('Edit')
            ->icon(LibraryIcon::FileDocumentOutline)
            ->color('gray')
            ->record(fn (array $arguments): ?OmrModel => $this->set->models->find($arguments['model'] ?? null))
            ->visible(fn (?OmrModel $record): bool => !is_null($record) && (Auth::user()?->can('update', $record) ?? false))
            ->modalHeading(fn (OmrModel $record): string => "Edit {$record->filename()}")
            ->modalSubmitActionLabel('Save')
            ->fillForm(function (OmrModel $record): array {
                $this->checks = [];

                return [
                    'user_id' => $record->user_id,
                    'alt_model' => $record->alt_model,
                    'alt_model_name' => $record->alt_model_name,
                    'missing_parts' => $record->missing_parts,
                    'missing_patterns' => $record->missing_patterns,
                    'missing_stickers' => $record->missing_stickers,
                    'notes' => $record->notes['notes'] ?? null,
                ];
            })
            ->schema(fn (OmrModel $record): array => [
                $this->modelFileField($record),
                $this->modelChecksView(),
                AuthorSelect::make()
                    ->required(),
                ...$this->modelDetailFields($record),
            ])
            ->action(function (array $data, OmrModel $record, OmrModelManager $manager): void {
                $this->authorize('update', $record);

                /** @var ?TemporaryUploadedFile $file */
                $file = $this->uploadedFile($data['modelfile'] ?? null);
                $manager->updateModel($record, User::findOrFail($data['user_id']), $data, $file?->get());

                $this->checks = [];
                $this->model_id = null;
                $this->set->unsetRelation('models');
            })
            ->successNotificationTitle('Model updated');
    }

    #[Layout('components.layout.omr')]
    public function render()
    {
        return view('livewire.omr.set.show');
    }
}
