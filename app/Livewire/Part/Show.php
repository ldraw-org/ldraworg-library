<?php

namespace App\Livewire\Part;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Enums\ExternalSite;
use App\Enums\LibraryIcon;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Enums\VoteType;
use App\Filament\Actions\EditHeaderAction;
use App\Filament\Actions\EditNumberAction;
use App\Filament\Actions\EditPreviewAction;
use App\Filament\Actions\Part\Download\PartFileDownloadAction;
use App\Filament\Actions\Part\Download\PartZipFileDownloadAction;
use App\Services\LDraw\Managers\Part\PartManager;
use App\Services\LDraw\Managers\VoteManager;
use App\Models\Part\Part;
use App\Models\Vote;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property \Filament\Schemas\Schema $form
 * @property Collection $baseparts
 * @property bool $hasSuffixParts
 */
class Show extends Component implements HasSchemas, HasActions
{
    use InteractsWithSchemas;
    use InteractsWithActions;

    public Part $part;
    public ?string $comment = null;
    public ?string $vote_type_code = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Comment / Vote')
                    ->schema([
                        Radio::make('vote_type_code')
                            ->label('')
                            ->options(fn () => $this->voteOptions())
                            ->default('M')
                            ->required()
                            ->markAsRequired(false)
                            ->enum(VoteType::class)
                            ->inline()
                            ->inlineLabel(false)
                            ->validationAttribute('vote type'),
                        Textarea::make('comment')
                            ->rows(5)
                            ->string()
                            ->nullable()
                            ->requiredIf('vote_type_code', ['M', 'H'])
                            ->extraAttributes(['class' => 'font-mono'])
                            ->validationMessages([
                                'required_if' => 'A comment is required',
                            ]),
                ])
            ]);
    }

    #[On('mass-vote')]
    public function voteOptions(): array
    {
        $options = [];
        foreach (VoteType::cases() as $vt) {
            if (Auth::user()->can('vote', [Vote::class, $this->part, $vt])) {
                $options[$vt->value] = $vt->label();
            }
        }
        return $options;
    }

    public function mount(Part $part, ?string $filename = null)
    {
        if ($part->exists) {
            $this->part = $part;
        } else {
            $this->part = Part::when(Str::startsWith($filename, 'unofficial/'),
                    fn (Builder $query) => $query->unofficial()
                )
                ->where('filename', Str::chopStart($filename, 'unofficial/'))
                ->orderBy('part_release_id', 'desc')
                ->firstOrFail();
        }
        $this->form->fill();
    }

    public function hasSuffixParts(): bool
    {
        if ($this->part->suffix_parts->isNotEmpty()) {
            return true;
        } elseif (!is_null($this->part->base_part)) {
            return $this->part->base_part->suffix_parts->isNotEmpty();
        } elseif (!is_null($this->part->official_part) && $this->part->official_part->suffix_parts->isNotEmpty()) {
            return true;
        }
        return false;
    }

    public function editHeaderAction(): EditAction
    {
        return EditHeaderAction::make($this->part, 'editHeader');
    }

    public function editNumberAction(): EditAction
    {
        return  EditNumberAction::make($this->part, 'editNumber');
    }

    public function editPreviewAction(): EditAction
    {
        return EditPreviewAction::make($this->part, 'editPreview');
    }

    public function patternPartAction(): Action
    {
        return Action::make('patternPart')
                ->url(fn () => route('parts.search.suffix', ['basepart' => basename(($this->part->base_part?->filename ?? $this->part->filename), '.dat')]))
                ->visible($this->hasSuffixParts())
                ->label('View patterns/composites/shortcuts')
                ->color('gray')
                ->outlined();
    }

    public function stickerSearchAction(): Action
    {
        return Action::make('stickerSearch')
                ->url(fn () => route('parts.sticker-sheet.show', $this->part->sticker_sheet ?? ''))
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

    public function updateRebrickableDataAction(): Action
    {
        return Action::make('updateRebrickableData')
                ->action(function () {
                    app(PartManager::class)->updateRebrickable($this->part);
                    Notification::make()
                        ->title('Rebrickable data refreshed')
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
                    $this->part->updatePartStatus();
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
                    app(PartManager::class)->loadSubparts($this->part);
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
        return PartFileDownloadAction::make('download', $this->part)
                ->color('gray')
                ->outlined();
    }

    public function downloadZipAction(): Action
    {
        return PartZipFileDownloadAction::make('zipdownload', $this->part)
                ->label('Download zip file')
                ->visible($this->part->type->inPartsFolder())
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
                    (Auth::user()?->can('allAdmin', [Vote::class, $this->part]) ?? false)
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
                    (Auth::user()?->can('allCertify', [Vote::class, $this->part]) ?? false)
                )
                ->color('gray')
                ->outlined();
    }

    public function postVote()
    {
        $this->form->getState();
        $vm = new VoteManager();
        $vm->castVote($this->part, Auth::user(), VoteType::tryFrom($this->vote_type_code), $this->comment);
        $this->dispatch('mass-vote');
        $this->form->fill();
    }

    public function toggleTrackedAction(): Action
    {
        return Action::make('toggleTracked')
            ->button()
            ->color(Auth::user()?->notification_parts->contains($this->part->id) ? 'yellow' : 'gray')
            ->icon(LibraryIcon::UserNotification->value)
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
            ->icon(LibraryIcon::PartFlag->value)
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
            ->icon(LibraryIcon::PartFlag->value)
            ->label($this->part->manual_hold_flag ? 'On Administrative Hold' : 'Place on Administrative Hold')
            ->action(function () {
                $this->part->manual_hold_flag = !$this->part->manual_hold_flag;
                $this->part->save();
            })
            ->visible(Auth::user()?->can('flagManualHold', $this->part) ?? false);
    }

    public function toggleIsPatternAction(): Action
    {
        return Action::make('toggleIsPattern')
            ->button()
            ->color(fn () => $this->part->is_pattern ? 'green' : 'gray')
            ->label($this->part->is_pattern ? 'Printed' : 'Not Printed')
            ->action(function () {
                $this->part->is_pattern = !$this->part->is_pattern;
                $this->part->save();
            })
            ->visible(Auth::user()?->can('update', $this->part) ?? false);
    }

    public function toggleIsCompositeAction(): Action
    {
        return Action::make('toggleIsComposite')
            ->button()
            ->color($this->part->is_composite ? 'green' : 'gray')
            ->label($this->part->is_composite ? 'Assembly' : 'Single Part')
            ->action(function () {
                $this->part->is_composite = !$this->part->is_composite;
                $this->part->save();
            })
            ->visible(Auth::user()?->can('update', $this->part) ?? false);
    }

    public function toggleIsDualMouldAction(): Action
    {
        return Action::make('toggleIsDualMould')
            ->button()
            ->color($this->part->is_dual_mould ? 'green' : 'gray')
            ->label($this->part->is_dual_mould ? 'Dual Moulded' : 'Single Mould')
            ->action(function () {
                $this->part->is_dual_mould = !$this->part->is_dual_mould;
                $this->part->save();
            })
            ->visible(Auth::user()?->can('update', $this->part) ?? false);
    }

    public function editBasePartAction(): Action
    {
        return EditAction::make('editBasePart')
            ->label('Edit Base Part')
            ->record($this->part)
            ->schema([
                Select::make('base_part_id')
                    ->searchable()
                    ->options(
                        Part::doesntHave('official_part')
                            ->partsFolderOnly()
                            ->where('is_pattern', false)
                            ->where('category', '!=', PartCategory::Moved)
                            ->whereNotLike('description', '%Obsolete%')
                            ->pluck('filename', 'id')
                    )
                    ->optionsLimit(50000),
            ])
            ->visible(Auth::user()?->can('update', $this->part) ?? false);
    }

    public function viewBasePartAction(): Action
    {
        return Action::make('viewBasePart')
            ->button()
            ->color('gray')
            ->label("Base Part: {$this->part->base_part?->name()}")
            ->url(route('parts.show', $this->part->base_part?->id ?? 0))
            ->visible(!is_null($this->part->base_part));
    }

    public function viewRebrickableAction(): Action
    {
        return $this->externalSiteAction(ExternalSite::Rebrickable);
    }

    public function viewBrickLinkAction(): Action
    {
        return $this->externalSiteAction(ExternalSite::BrickLink);
    }

    public function viewBrickOwlAction(): Action
    {
        return $this->externalSiteAction(ExternalSite::BrickOwl);
    }

    public function viewBricksetAction(): Action
    {
        return $this->externalSiteAction(ExternalSite::Brickset);
    }

    protected function externalSiteAction(ExternalSite $site): Action
    {
        $url = $site->url($this->part->getExternalSiteNumber($site));
        return Action::make("view{$site->name}")
            ->button()
            ->color('gray')
            ->label("View on {$site->name}")
            ->icon(LibraryIcon::ExternalSite->value)
            ->iconPosition(IconPosition::After)
            ->url($url ?? '', shouldOpenInNewTab: true)
            ->visible(!is_null($url));
    }
    public function viewFixAction(): Action
    {
        return Action::make('viewFix')
            ->button()
            ->color('gray')
            ->icon(LibraryIcon::PartFix->value)
            ->label('View ' . ($this->part->isUnofficial() ? 'official' : 'unofficial')  . ' version of part')
            ->url(function () {
                if ($this->part->isUnofficial()) {
                    return route('parts.show', $this->part->official_part->id ?? 0);
                }
                return route('parts.show', $this->part->unofficial_part->id ?? 0);
            })
            ->visible(!is_null($this->part->unofficial_part) || !is_null($this->part->official_part));
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.part.show');
    }
}
