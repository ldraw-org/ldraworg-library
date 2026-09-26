<?php

namespace App\Livewire\Part;

use App\Enums\ExternalSite;
use App\Enums\VoteType;
use App\Filament\Actions\EditHeaderAction;
use App\Filament\Actions\EditNumberAction;
use App\Filament\Actions\EditPreviewAction;
use App\Services\Part\GenerateHeader;
use App\Services\Part\ImageGenerator;
use App\Services\Part\RebrickableSync;
use App\Services\Part\SyncSubparts;
use App\Services\Part\Validator;
use App\Services\Vote\VoteManager;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Enums\LibraryIcon;
use App\Models\Part\Part;
use App\Models\Vote;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property Schema $form
 */
class Show extends Component implements HasSchemas, HasActions
{
    use InteractsWithSchemas;
    use InteractsWithActions;

    public Part $part;
    public array $data = [];
    public bool $webGlSupported = false;

    public function mount(Part $part, ?string $filename = null)
    {
        if ($part->exists) {
            $this->part = $part;
        } else {
            $this->part = Part::when(
                Str::startsWith($filename, 'unofficial/'),
                fn (Builder $query) => $query->unofficial()
            )
                ->where('filename', Str::chopStart($filename, 'unofficial/'))
                ->orderBy('part_release_id', 'desc')
                ->firstOrFail();
        }
        $this->form->fill();
    }

    // Vote form functions

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Comment / Vote')
                    ->schema([
                        Radio::make('vote_type')
                            ->label('')
                            ->default(VoteType::Comment)
                            ->options(function () {
                                $user = Auth::user();
                                $options = [];
                                foreach (VoteType::cases() as $vt) {
                                    if ($user?->can('vote', [Vote::class, $this->part, $vt])) {
                                        $options[$vt->value] = $vt->label();
                                    }
                                }
                                return $options;
                            })
                            ->required()
                            ->markAsRequired(false)
                            ->enum(VoteType::class)
                            ->inline()
                            ->inlineLabel(false)
                            ->live()
                            ->validationAttribute('vote type'),
                        Textarea::make('comment')
                            ->rows(5)
                            ->string()
                            ->nullable()
                            ->markAsRequired(false)
                            ->requiredIf('vote_type', [VoteType::Comment, VoteType::Hold])
                            ->extraAttributes(['class' => 'font-mono'])
                            ->validationMessages([
                                'required_if' => 'A comment is required',
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function postVote(): void
    {
        $data = $this->form->getState();
        $vm = app(VoteManager::class);
        $vm->castVote($this->part, Auth::user(), $data['vote_type'], $data['comment'] ?? null);
        $this->part->refresh();
        $this->form->fill();
    }

    // In line functions
    public function trackPartAction(): Action
    {
        $partIsFollowed = $this->part->notification_users->contains(Auth::user()?->id);
        $color = $partIsFollowed ? 'yellow' : 'gray';
        return $this->buttonAction('trackPart', $color)
            ->tooltip($partIsFollowed ? 'Click to stop following this part' : 'Click to follow this part')
            ->label('')
            ->icon($partIsFollowed ? LibraryIcon::Bell : LibraryIcon::BellOff)
            ->action(function () {
                $this->part->notification_users()->toggle(Auth::user()?->id);
                $this->part->refresh();
            })
            ->visible($this->part->isUnofficial() && Auth::check());
    }

    public function updateImageAction(): Action
    {
        return $this->buttonAction('updateImage')
            ->action(action: function () {
                app(ImageGenerator::class)->regenerateImage($this->part);
                $this->sendFilamentNotification('Image regenerated');
            })
            ->label('Regenerate part image')
            ->icon(LibraryIcon::ImageRefresh)
            ->visible(Auth::user()?->can('update', $this->part) ?? false);
    }

    protected function editHeaderAction(): EditAction
    {
        return EditHeaderAction::make('editHeader')
            ->color('gray')
            ->outlined()
            ->icon(LibraryIcon::EditHeader)
            ->record($this->part);
    }

    public function regenerateHeaderAction(): Action
    {
        return $this->buttonAction('regenerateHeader')
            ->action(function () {
                app(GenerateHeader::class)->updatePartHeader($this->part);
                $this->part->save();
                $this->sendFilamentNotification('Part header regenerated');
            })
            ->icon(LibraryIcon::HeaderRefresh)
            ->visible(Auth::user()?->can('update', $this->part) ?? false);
    }

    public function recheckPartAction(): Action
    {
        return $this->buttonAction('recheckPart')
            ->label('Rerun Error Checks')
            ->icon(LibraryIcon::Refresh)
            ->action(function () {
                app(Validator::class)->checkPart($this->part);
                $this->sendFilamentNotification('Part Error Checked');
            })
            ->visible(Auth::user()?->can('update', $this->part) ?? false);
    }

    public function rescanSubpartsAction(): Action
    {
        return $this->buttonAction('rescanSubparts')
            ->action(function () {
                app(SyncSubparts::class)->loadSubparts($this->part);
                $this->part->refresh();
                $this->sendFilamentNotification('Subparts rescanned');
            })
            ->icon(LibraryIcon::SubpartsRefresh)
            ->label('Rescan subparts')
            ->visible(Auth::user()?->can('update', $this->part) ?? false);
    }

    protected function editNumberAction(): EditAction
    {
        return EditNumberAction::make('editNumber')
            ->color('gray')
            ->record($this->part);
    }

    public function updateRebrickableDataAction(): Action
    {
        return $this->buttonAction('updateRebrickableData')
            ->action(function () {
                app(RebrickableSync::class)->syncRebrickablePart($this->part);
                $this->part->refresh();
                $this->sendFilamentNotification('Rebrickable data refreshed');
            })
            ->icon(LibraryIcon::Refresh)
            ->tooltip('Recheck site data from Rebrickable')
            ->hiddenLabel()
            ->visible(Auth::user()?->can('update', $this->part) ?? false);
    }

    // Top menu actions

    protected function downloadAction(): Action
    {
        $route = route('part.download', ['library' => $this->part->libFolder(), 'filename' => $this->part->filename]);
        return $this->buttonAction('download')
            ->url($route)
            ->icon(LibraryIcon::Download);
    }

    protected function downloadZipAction(): Action
    {
        $zipFilename = Str::replaceLast('.dat', '.zip', $this->part->filename);
        $route = route('part.download', ['library' => $this->part->libFolder(), 'filename' => $zipFilename]);
        return $this->buttonAction('download')
            ->url($route)
            ->label('Download zip file')
            ->visible($this->part->type->inPartsFolder())
            ->icon(LibraryIcon::DownloadZip);
    }

    protected function patternPartAction(): Action
    {
        $route = route('parts.search.suffix', ['basepart' => basename(($this->part->base_part?->filename ?? $this->part->filename), '.dat')]);
        return $this->buttonAction('patternPart')
            ->url($route)
            ->visible($this->hasSuffixParts())
            ->label('View patterns/composites/shortcuts');
    }

    protected function stickerSearchAction(): Action
    {
        $sticker_cat_id = 58;
        $route = '';
        if ($this->part->rebrickable_part?->rb_part_category_id === $sticker_cat_id) {
            $route = route('parts.sticker-sheet.show', $this->part->rebrickable_part);
        }
        return $this->buttonAction('stickerSearch')
            ->url($route)
            ->visible($this->part->rebrickable_part?->rb_part_category_id === $sticker_cat_id)
            ->label('View sticker sheet parts');
    }

    public function adminCertifyAllAction(): Action
    {
        return $this->massCertAction('adminCertifyAll', 'allAdmin');
    }

    public function certifyAllAction(): Action
    {
        return $this->massCertAction('certifyAll', 'allCertify');
    }

    public function topMenuActionGroup(): ActionGroup
    {
        return ActionGroup::make([
            $this->downloadAction(),
            $this->downloadZipAction(),
            $this->patternPartAction(),
            $this->stickerSearchAction(),
            $this->certifyAllAction(),
            $this->adminCertifyAllAction(),
        ])
            ->buttonGroup();
    }

    // Admin tools actions

    protected function editPreviewAction(): EditAction
    {
        return EditPreviewAction::make('editPreview')
            ->record($this->part);
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
            ->button()
            ->outlined()
            ->hiddenLabel()
            ->icon(LibraryIcon::Remove)
            ->tooltip('Delete this part')
            ->modalDescription('Are you sure you\'d like to delete this part? This cannot be easily undone.')
            ->successRedirectUrl(route('tracker.activity'))
            ->successNotificationTitle('Part deleted');
    }

    public function externalSiteActionGroup(): ActionGroup
    {
        return ActionGroup::make([
            $this->externalSiteAction(ExternalSite::Rebrickable),
            $this->externalSiteAction(ExternalSite::BrickLink),
            $this->externalSiteAction(ExternalSite::BrickOwl),
            $this->externalSiteAction(ExternalSite::Brickset),
            $this->updateRebrickableDataAction(),
        ])
        ->label('External Sites')
        ->buttonGroup();
    }

    public function partOperationsActionGroup(): ActionGroup
    {
        return ActionGroup::make([
            $this->recheckPartAction(),
            $this->rescanSubpartsAction(),
            $this->updateImageAction(),
            $this->editPreviewAction(),
            $this->regenerateHeaderAction(),
            $this->editHeaderAction(),
            $this->editNumberAction(),
        ])
            ->label('Admin Operations')
            ->buttonGroup();
    }
    // Utility Methods

    protected function hasSuffixParts(): bool
    {
        if ($this->part->suffix_parts->isNotEmpty()) {
            return true;
        }
        if (!is_null($this->part->base_part)) {
            return $this->part->base_part->suffix_parts->isNotEmpty();
        }
        if (!is_null($this->part->official_part) && $this->part->official_part->suffix_parts->isNotEmpty()) {
            return true;
        }
        return false;
    }

    protected function buttonAction(string $name, string $color = 'gray'): Action
    {
        return Action::make($name)
            ->color($color)
            ->outlined();
    }

    protected function externalSiteAction(ExternalSite $site): Action
    {
        $url = $site->url($this->part->getExternalSiteNumber($site));
        return $this->buttonAction("view{$site->name}")
            ->label("View on {$site->name}")
            ->url($url ?? '', shouldOpenInNewTab: true)
            ->visible(!is_null($url));
    }

    protected function massCertAction(string $ability, string $gate): Action
    {
        $user = Auth::user();
        $userCanVote = $user?->can($gate, [Vote::class, $this->part]) ?? false;
        return $this->buttonAction($ability)
            ->action(function () use ($user, $ability) {
                $vm = app(VoteManager::class);
                $vm->{$ability}($this->part, $user);
                $this->part->refresh();
                Notification::make()
                    ->title('Quickvote action complete')
                    ->success()
                    ->send();
            })
            ->visible($this->part->isUnofficial() && $userCanVote);
    }

    protected function sendFilamentNotification(string $message): void
    {
        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.part.show');
    }
}
