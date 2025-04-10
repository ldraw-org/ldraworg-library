<div x-data="{ webgl: true }">
    <x-slot:title>
        File Detail {{ $part->filename }}
    </x-slot>
    <x-slot:breadcrumbs>
        <x-breadcrumb-item class="active" item="Part Detail" />
    </x-slot>
    @push('meta')
        <meta name="description" content="{{$part->description}}">

        <!-- Facebook Meta Tags -->
        <meta property="og:url" content="{{Request::url()}}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="File Detail {{ $part->filename }}">
        <meta property="og:description" content="{{$part->description}}">
        <meta property="og:image" content="{{$image}}">

        <!-- Twitter Meta Tags -->
        <meta name="twitter:card" content="summary_large_image">
        <meta property="twitter:domain" content="library.ldraw.org">
        <meta property="twitter:url" content="{{Request::url()}}">
        <meta name="twitter:title" content="File Detail {{ $part->filename }}">
        <meta name="twitter:description" content="{{$part->description}}">
        <meta name="twitter:image" content="{{$image}}">
    @endpush

    <div class="flex flex-col space-y-4">
        <div class="flex flex-wrap gap-2">
            <x-filament-action action="downloadAction" />
            <x-filament-action action="downloadZipAction" />
            <x-filament-action action="patternPartAction" />
            <x-filament-action action="stickerSearchAction" />
            <x-filament-action action="adminCertifyAllAction" />
            <x-filament-action action="certifyAllAction" />
            @if (!$part->isTexmap())
                <x-filament::button
                    color="gray"
                    outlined
                    wire:click="$dispatch('open-modal', { id: 'ldbi' })"
                >
                    3D View
                </x-filament::button>
            @endif

            @if ($this->editHeaderAction->isVisible() ||
                $this->editNumberAction->isVisible() ||
                $this->editPreviewAction->isVisible() ||
                $this->editBasePartAction->isVisible() ||
                $this->updateImageAction->isVisible() ||
                $this->recheckPartAction->isVisible() ||
                $this->updateSubpartsAction->isVisible() ||
                $this->updateRebrickableDataAction->isVisible() ||
                $this->retieFixAction->isVisible() ||
                $this->deleteAction->isVisible()
            )

                <x-filament-actions::group
                    :actions="[
                        $this->editHeaderAction,
                        $this->editNumberAction,
                        $this->editPreviewAction,
                        $this->editBasePartAction,
                        $this->updateImageAction,
                        $this->recheckPartAction,
                        $this->updateSubpartsAction,
                        $this->updateRebrickableDataAction,
                        $this->retieFixAction,
                        $this->deleteAction
                    ]"
                    label="Admin Tools"
                    icon="fas-caret-down"
                    button="true"
                    color="gray"
                    outlined="true"
                />
            @endif
        </div>
        <div @class([
                'text-3xl font-bold py-2 px-3 w-fit rounded-lg',
                'bg-green-100' => $part->isOfficial(),
                'bg-yellow-100' => $part->isUnofficial()
            ])>
                {{ucfirst($part->libFolder())}} File <span id="filename">{{ $part->filename }}</span>
        </div>
        <div>
            <x-filament-action action="viewFixAction" />
            @if ($part->isUnofficial())
                <x-filament-action action="toggleTrackedAction" />
                <x-filament-action action="toggleDeleteFlagAction" />
                @if($part->delete_flag && !$this->toggleDeleteFlagAction->isVisible())
                    <x-filament::button
                        icon="fas-flag"
                        color="danger"
                    >
                        Flagged for Deletion
                    </x-filament::button>
                @endif
                <x-filament-action action="toggleManualHoldAction" />
            @endif
        </div>
        @if ($this->part->type->inPartsFolder())
            <div>
                <span class="font-bold text-lg">
                    Part Attributes:
                </span>
                <x-filament-action action="viewBasePartAction" />
                <x-filament-action action="toggleIsPatternAction" />
                @if (!$this->toggleIsPatternAction->isVisible())
                    <x-filament::button
                        color="gray"
                    >
                    {{$this->part->is_pattern ? 'Printed' : 'Not Printed'}}
                    </x-filament::button>
                @endif
                <x-filament-action action="toggleIsCompositeAction" />
                @if (!$this->toggleIsCompositeAction->isVisible())
                    <x-filament::button
                        color="gray"
                    >
                        {{$this->part->is_composite ? 'Assembly' : 'Single Part'}}
                    </x-filament::button>
                @endif
                <x-filament-action action="toggleIsDualMouldAction" />
                @if (!$this->toggleIsDualMouldAction->isVisible())
                    <x-filament::button
                        color="gray"
                    >
                        {{$this->part->is_dual_mould ? 'Dual Moulded' : 'Single Mould'}}
                    </x-filament::button>
                @endif
            </div>
            @if ($this->viewRebrickableAction->isVisible() ||
                $this->viewBrickLinkAction->isVisible() ||
                $this->viewBricksetAction->isVisible() ||
                $this->viewBrickOwlAction->isVisible()
            )
                <div class="flex space-x-2 place-items-center">
                    <div class="font-bold text-lg">
                        External Sites:
                    </div>
                    @if (!is_null($part->getRebrickablePart()))
                        <x-fas-link class="w-5 h-5 fill-gray-400" title="External site data provided by Rebrickable.com" />
                    @else
                        <x-fas-link-slash class="w-5 h-5 fill-gray-400" title="External site data provided by part keywords" />
                    @endif
                    <x-filament-action action="viewRebrickableAction" />
                    <x-filament-action action="viewBrickLinkAction" />
                    <x-filament-action action="viewBrickSetAction" />
                    <x-filament-action action="viewBrickOwlAction" />
                </div>
            @endif
        @endif
        <div class="w-full p-4 border rounded-md">
          <div class="flex flex-col md:flex-row-reverse w-full">
            <div class="flex w-full justify-center items-center md:w-1/3">
              <img class = 'w-80 h-80 object-contain'
                @if(!$part->isTexmap()) wire:click="$dispatch('open-modal', { id: 'ldbi' })" @endif
                src="{{$image}}" alt="{{ $part->description }}" title="{{ $part->description }}">
            </div>
            <div class="w-full md:w-2/3">
              <div class="justify-self-start w-full">
                <div class="text-lg font-bold">File Header:</div>
                <code class="whitespace-pre-wrap break-words font-mono">{{ trim($part->header) }}</code>
              </div>
            </div>
          </div>
          <div class="w-full">
            <x-accordion id="showContents">
              <x-slot name="header" class="text-md font-bold pt-4">
                Show contents
              </x-slot>
              <code class="whitespace-pre-wrap break-words font-mono">{{ trim($part->body->body) }}</code>
            </x-accordion>
          </div>
        </div>
        @if($part->isUnofficial())
            <div class="text-lg font-bold">Status:</div>
            <x-part.status :$part show-status />
            @empty($part->can_release || $part->part_check->get(['warnings']))
                <x-message.not-releaseable :$part />
            @endempty
            <div class="text-md font-bold">Current Votes:</div>
            <x-vote.table :votes="$part->votes" />
            @if (count($part->missing_parts) > 0)
                <div class="text-md font-bold">Missing Part References:</div>
                @foreach($part->missing_parts as $missing)
                    <div class="text-red-500">{{ $missing }}</div>
                @endforeach
            @endif
            <livewire:tables.part-dependencies-table :$part parents />
            <livewire:tables.part-dependencies-table :$part />
            <x-accordion id="officialParts">
                <x-slot name="header" class="text-md font-bold">
                    Official parents and subparts
                </x-slot>
                <livewire:tables.part-dependencies-table :$part official parents />
                <livewire:tables.part-dependencies-table :$part official />
            </x-accordion>
        @else
            <livewire:tables.part-dependencies-table :$part official parents />
            <livewire:tables.part-dependencies-table :$part official />
            <x-accordion id="unofficialParents">
                <x-slot name="header" class="text-md font-bold">
                    Unofficial parents
                </x-slot>
                <livewire:tables.part-dependencies-table :$part unofficial parents />
            </x-accordion>
        @endif
        <div class="text-lg font-bold">Part Events:</div>
        <div class="flex flex-col space-y-4">
            @if ($part->isOfficial() || !is_null($part->official_part))
                <x-accordion id="archiveEvents">
                    <x-slot name="header">
                        Archived Part Events:
                    </x-slot>
                    @forelse ($part->events->official()->sortBy('created_at') as $event)
                        <x-event.list.item :$event wire:key="part-event-{{$event->id}}" />
                    @empty
                        <div>None</div>
                    @endforelse
                </x-accordion>
            @endif
            @if ($part->isUnofficial())
                @forelse ($part->events->unofficial()->sortBy('created_at') as $event)
                    <x-event.list.item :$event wire:key="part-event-{{$event->id}}" />
                @empty
                    <div>No Events</div>
                @endforelse
            @endif
        </div>
        @if($part->isUnofficial())
            @can('voteAny', [\App\Models\Vote::class, $this->part])
                <div id="voteForm"></div>
                <form wire:submit="postVote">
                    {{ $this->form }}
                    <x-filament::button type="submit">
                        <x-filament::loading-indicator wire:loading wire:target="postVote" class="h-5 w-5" />
                        Vote
                    </x-filament::button>
                </form>
            @endcan
        @endif
        <x-part.attribution :part="$part" />
    </div>
    <x-filament::modal id="ldbi" alignment="center" width="7xl" lazy>
        <x-slot name="heading">
            3D View
        </x-slot>
        <div class="flex flex-col w-full h-full">
            <div class="flex flex-row space-x-2 p-2 mb-2">
                <x-filament::icon-button
                    icon="fas-undo"
                    size="lg"
                    label="Normal mode"
                    class="border"
                    wire:click="$dispatch('ldbi-default-mode')"
                />
                <x-filament::icon-button
                    icon="fas-paint-brush"
                    size="lg"
                    label="Harlequin (random color) mode"
                    class="border"
                    wire:click="$dispatch('ldbi-harlequin-mode')"
                />
                <x-filament::icon-button
                    icon="fas-leaf"
                    size="lg"
                    label="Back Face Culling (BFC) mode"
                    class="border"
                    wire:click="$dispatch('ldbi-bfc-mode')"
                />
                <x-filament::icon-button
                    icon="fas-dot-circle"
                    size="lg"
                    label="Toggle Stud Logos"
                    class="border"
                    wire:click="$dispatch('ldbi-stud-logos')"
                />
                <x-filament::icon-button
                    icon="fas-arrows-alt"
                    size="lg"
                    label="Toggle Show Origin"
                    class="border"
                    wire:click="$dispatch('ldbi-show-origin')"
                />
                <x-filament::icon-button
                    icon="fas-eye"
                    size="lg"
                    label="Toggle Photo Mode"
                    class="border"
                    wire:click="$dispatch('ldbi-physical-mode')"
                />
            </div>
            <x-3d-viewer class="border w-full h-[80vh]" partname="{{str_replace('\\', '/', $part->name())}}" modelid="{{$part->id}}"/>
        </div>
    </x-filament::modal>
    <x-filament-actions::modals />
    @push('scripts')
        @script
        <script>
            $wire.on('open-modal', (modal) => {
                if (scene == null) {
                    $wire.dispatch('ldbi-render-model');
                }
            });
        </script>
        @endscript
    @endpush
</div>
