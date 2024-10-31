<?php

namespace App\Livewire\Part;

use App\Filament\Part\Actions\EditHeaderAction;
use App\Filament\Part\Actions\EditNumberAction;
use App\LDraw\PartManager;
use App\LDraw\VoteManager;
use App\Models\Part;
use App\Models\Vote;
use App\Models\VoteType;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property Form $form
 * @property Collection $baseparts
 * @property bool $hasSuffixParts
 */
class Show extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public Part $part;
    public ?string $comment = null;
    public ?string $vote_type_code = null;
    public string $image;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Comment / Vote')
                    ->schema([
                        Radio::make('vote_type_code')
                            ->label('')
                            ->options(fn () => $this->voteOptions())
                            ->default('M')
                            ->required()
                            ->markAsRequired(false)
                            ->in(fn () => $this->voteTypes()->pluck('code')->all())
                            ->inline()
                            ->inlineLabel(false)
                            ->validationAttribute('vote type'),
                        Textarea::make('comment')
                            ->rows(5)
                            ->string()
                            ->nullable()
                            ->requiredIf('vote_type_code', ['M', 'H'])
                            ->validationMessages([
                                'required_if' => 'A comment is required',
                            ]),
                ])
            ]);
    }

    #[Computed]
    public function voteTypes()
    {
        return VoteType::ordered()->get();
    }

    #[Computed]
    public function voteOptions(): array
    {
        if (!Auth::check()) {
            return [];
        }
        $options = [];
        $u = Auth::user();
        $v = $this->part->votes->firstWhere('user_id', $u->id);
        foreach ($this->voteTypes() as $vt) {
            switch ($vt->code) {
                case 'N':
                    if (!is_null($v) && $u->can('update', [$v, $vt->code])) {
                        $options[$vt->code] = $vt->name;
                    }
                    break;
                default:
                    if (
                        (is_null($v) && $u->can('create', [Vote::class, $this->part, $vt->code])) ||
                        $u->can('update', [$v, $vt->code])
                    ) {
                        if (is_null($v) || $v->vote_type_code != $vt->code) {
                            $options[$vt->code] = $vt->name;
                        }
                    }
            }
        }
        return $options;
    }
    public function mount(?Part $part, ?Part $unofficialpart, ?Part $officialpart)
    {
        if (!is_null($part) && $part->exists) {
            $this->part = $part;
        } elseif (!is_null($unofficialpart) && $unofficialpart->exists) {
            $this->part = $unofficialpart;
        } elseif (!is_null($officialpart) && $officialpart->exists) {
            $this->part = $officialpart;
        } else {
            return response('404');
        }
        $this->image =
            $this->part->isTexmap() ? route("{$this->part->libFolder()}.download", $this->part->filename) : version("images/library/{$this->part->libFolder()}/" . substr($this->part->filename, 0, -4) . '.png');
        $this->form->fill();
    }

    public function hasSuffixParts(): bool
    {
        if ($this->part->suffix_parts->count() > 0) {
            return true;
        }
        return $this->part->base_part?->suffix_parts->count() > 0 ?? false;
    }

    public function editHeaderAction(): EditAction
    {
        return EditHeaderAction::make($this->part, 'editHeader');
    }

    public function editNumberAction(): EditAction
    {
        return  EditNumberAction::make($this->part, 'editNumber');
    }

    public function patternPartAction(): Action
    {
        return Action::make('patternPart')
                ->url(fn () => route('search.suffix', ['basepart' => basename(($this->part->base_part?->filename ?? ''), '.dat')]))
                ->visible($this->hasSuffixParts())
                ->label('View patterns/composites/shortcuts')
                ->color('gray')
                ->outlined();
    }

    public function stickerSearchAction(): Action
    {
        return Action::make('stickerSearch')
                ->url(fn () => route('sticker-sheet.show', $this->part->sticker_sheet ?? ''))
                ->visible(!is_null($this->part->sticker_sheet_id))
                ->label('View sticker sheet parts')
                ->color('gray')
                ->outlined();
    }

    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make('delete')
                ->record($this->part)
                ->visible(
                    $this->part->isUnofficial() &&
                    (!is_null($this->part->official_part) || $this->part->parents->count() === 0) &&
                    (Auth::user()?->can('delete', $this->part) ?? false)
                )
                ->modalDescription('Are you sure you\'d like to delete this part? This cannot be easily undone.')
                ->successRedirectUrl(route('tracker.activity'))
                ->successNotificationTitle('Part deleted');
    }

    public function updateImageAction(): Action
    {
        return Action::make('updateImage')
                ->action(function () {
                    app(PartManager::class)->updateImage($this->part);
                    $this->dispatch('subparts-updated');
                    Notification::make()
                        ->title('Image Updated')
                        ->success()
                        ->send();
                })
                ->visible(Auth::user()?->can('update', $this->part) ?? false);
    }

    public function recheckPartAction(): Action
    {
        return Action::make('recheckPart')
                ->action(function () {
                    app(PartManager::class)->checkPart($this->part);
                    $this->part->updateVoteSort();
                    $this->dispatch('subparts-updated');
                    Notification::make()
                        ->title('Part Error Checked')
                        ->success()
                        ->send();
                })
                ->visible(Auth::user()?->can('update', $this->part) ?? false);
    }

    public function updateSubpartsAction(): Action
    {
        return Action::make('updateSubparts')
                ->action(function () {
                    app(PartManager::class)->loadSubpartsFromBody($this->part);
                    $this->dispatch('subparts-updated');
                    Notification::make()
                        ->title('Subparts Reloaded')
                        ->success()
                        ->send();
                })
                ->visible(Auth::user()?->can('update', $this->part) ?? false);
    }

    public function retieFixAction(): Action
    {
        return Action::make('retieFix')
                ->label('Retie part fix')
                ->action(function () {
                    if ($this->part->isUnofficial()) {
                        $fixpart = Part::official()->firstWhere('filename', $this->part->filename);
                        $fixpart->unofficial_part()->associate($this->part);
                        $fixpart->save();
                    } else {
                        $fixpart = Part::unofficial()->firstWhere('filename', $this->part->filename);
                        $this->part->unofficial_part()->associate($fixpart);
                        $this->part->save();
                    }
                    $this->part->refresh();
                })
                ->visible(function (): bool {
                    if (!Auth::check() ||
                        Auth::user()?->cannot('update', $this->part) ||
                        Part::where('filename', $this->part->filename)->count() <= 1
                    ) {
                        return false;
                    }
                    return is_null($this->part->unofficial_part) && is_null($this->part->official_part);
                });
    }

    public function downloadAction(): Action
    {
        return Action::make('download')
                ->url(fn () => route($this->part->isUnofficial() ? 'unofficial.download' : 'official.download', $this->part->filename))
                ->color('gray')
                ->outlined();
    }

    public function downloadZipAction(): Action
    {
        return Action::make('zipdownload')
                ->label('Download zip file')
                ->url(fn () => route($this->part->isUnofficial() ? 'unofficial.download.zip' : 'official.download.zip', str_replace('.dat', '.zip', $this->part->filename)))
                ->visible($this->part->type->folder == 'parts/')
                ->color('gray')
                ->outlined();
    }

    public function webglViewAction(): Action
    {
        return Action::make('webglView')
                ->label('3D View')
                ->action(fn () => $this->dispatch('open-modal', id: 'ldbi'))
                ->color('gray')
                ->outlined();
    }

    public function adminCertifyAllAction(): Action
    {
        return Action::make('adminCertifyAll')
                ->action(function () {
                    $vm = new VoteManager();
                    $vm->adminCertifyAll($this->part, Auth::user());
                    $this->part->refresh();
                    $this->dispatch('mass-vote');
                    Notification::make()
                        ->title('Quickvote action complete')
                        ->success()
                        ->send();
                })
                ->visible(
                    $this->part->isUnofficial() &&
                    $this->part->type->folder == 'parts/' &&
                    $this->part->ready_for_admin === true &&
                    $this->part->descendantsAndSelf->where('vote_sort', 2)->count() > 0 &&
                    (Auth::user()?->can('create', [Vote::class, $this->part, 'A']) ?? false) &&
                    (Auth::user()?->can('allAdmin', Vote::class) ?? false)
                )
                ->color('gray')
                ->outlined();
    }

    public function certifyAllAction(): Action
    {
        return Action::make('certifyAll')
                ->action(function () {
                    $vm = new VoteManager();
                    $vm->certifyAll($this->part, Auth::user());
                    $this->part->refresh();
                    $this->dispatch('mass-vote');
                    Notification::make()
                        ->title('Quickvote action complete')
                        ->success()
                        ->send();
                })
                ->visible(
                    $this->part->isUnofficial() &&
                    $this->part->type->folder == 'parts/' &&
                    $this->part->descendantsAndSelf->where('vote_sort', '>', 3)->count() == 0 &&
                    $this->part->descendantsAndSelf->where('vote_sort', 3)->count() > 0 &&
                    (Auth::user()?->can('create', [Vote::class, $this->part, 'C']) ?? false) &&
                    (Auth::user()?->can('all', Vote::class) ?? false)
                )
                ->color('gray')
                ->outlined();
    }

    public function postVote()
    {
        $this->form->getState();
        $vm = new VoteManager();
        $vm->postVote($this->part, Auth::user(), $this->vote_type_code, $this->comment);
        $this->dispatch('mass-vote');
        $this->form->fill();
    }

    public function toggleTrackedAction(): Action
    {
        return Action::make('toggleTracked')
            ->button()
            ->color(Auth::user()?->notification_parts->contains($this->part->id) ? 'yellow' : 'gray')
            ->icon('fas-bell')
            ->label(Auth::user()?->notification_parts->contains($this->part->id) ? 'Tracking' : 'Track')
            ->action(function () {
                Auth::user()->notification_parts()->toggle([$this->part->id]);
            })
            ->visible(Auth::check());
    }

    public function toggleDeleteFlagAction(): Action
    {
        return Action::make('toggleDeleteFlag')
            ->button()
            ->color($this->part->delete_flag ? 'red' : 'gray')
            ->icon('fas-flag')
            ->label($this->part->delete_flag ? 'Flagged for Deletion' : 'Flag for Deletion')
            ->action(function () {
                $this->part->delete_flag = !$this->part->delete_flag;
                $this->part->save();
            })
            ->visible(Auth::user()?->can('flagDelete', $this->part) ?? false);
    }

    public function toggleManualHoldAction(): Action
    {
        return Action::make('toggleManualHold')
            ->button()
            ->color($this->part->manual_hold_flag ? 'red' : 'gray')
            ->icon('fas-flag')
            ->label($this->part->manual_hold_flag ? 'On Administrative Hold' : 'Place on Administrative Hold')
            ->action(function () {
                $this->part->manual_hold_flag = !$this->part->manual_hold_flag;
                $this->part->save();
            })
            ->visible(Auth::user()?->can('flagManualHold', $this->part) ?? false);
    }

    public function viewFixAction(): Action
    {
        return Action::make('viewFix')
            ->button()
            ->color('gray')
            ->icon('fas-copy')
            ->label('View ' . ($this->part->isUnofficial() ? 'official' : 'unofficial')  . ' version of part')
            ->url(function () {
                if ($this->part->isUnofficial()) {
                    return route('official.show', $this->part->official_part->id ?? 0);
                }
                return route('tracker.show', $this->part->unofficial_part->id ?? 0);
            })
            ->visible(!is_null($this->part->unofficial_part) || !is_null($this->part->official_part));
    }

    public function adminErrorAction(): Action
    {
        return Action::make('adminError')
            ->button()
            ->color('gray')
            ->icon('fas-copy')
            ->label(true ? 'Edit/Clear Admin Error' : 'Add Admin Error')
            ->url(function () {
                if ($this->part->isUnofficial()) {
                    return route('official.show', $this->part->official_part->id ?? 0);
                }
                return route('tracker.show', $this->part->unofficial_part->id ?? 0);
            })
            ->visible(false);
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.part.show');
    }
}
