<div x-data="{ webgl: true }">
    <x-slot:title>
        File Detail {{ $part->filename }}
    </x-slot>
    @push('meta')
        <meta name="description" content="{{$part->description}}">

        <!-- Facebook Meta Tags -->
        <meta property="og:url" content="{{Request::url()}}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="File Detail {{ $part->filename }}">
        <meta property="og:description" content="{{$part->description}}">
        <meta property="og:image" content="{{$part->getFirstMediaUrl('image', 'feed-image')}}">

        <!-- Twitter Meta Tags -->
        <meta name="twitter:card" content="summary_large_image">
        <meta property="twitter:domain" content="library.ldraw.org">
        <meta property="twitter:url" content="{{Request::url()}}">
        <meta name="twitter:title" content="File Detail {{ $part->filename }}">
        <meta name="twitter:description" content="{{$part->description}}">
        <meta name="twitter:image" content="{{$part->getFirstMediaUrl('image', 'feed-image')}}">
    @endpush

    <div class="flex flex-col space-y-4 bg-white p-2 rounded-lg">
        <div class="flex flex-wrap gap-2">
            <x-filament-action :action="$this->downloadAction" />
            <x-filament-action :action="$this->downloadZipAction" />
            <x-filament-action :action="$this->patternPartAction" />
            <x-filament-action :action="$this->stickerSearchAction" />
            <x-filament-action :action="$this->adminCertifyAllAction" />
            <x-filament-action :action="$this->certifyAllAction" />
            @if (!$part->isTexmap())
                <x-filament::button
                    color="gray"
                    outlined
                    wire:click="$dispatch('open-modal', { id: 'ldbi' })"
                >
                    3D View
                </x-filament::button>
            @endif
            <x-filament-action-group :group="$this->adminToolsActionGroup()" />
        </div>
        <div @class([
                'text-3xl font-bold py-2 px-3 w-fit rounded-lg',
                'bg-green-100' => $part->isOfficial(),
                'bg-yellow-100' => $part->isUnofficial()
            ])>
                {{ucfirst($part->libFolder())}} File <span id="filename">{{ $part->filename }}</span>
        </div>
        <div>
            <x-filament-action :action="$this->viewFixAction" />
            @if ($part->isUnofficial())
                <x-filament-action :action="$this->toggleTrackedAction" />
                <x-filament-action
                    :action="$this->toggleDeleteFlagAction"
                    @if($part->delete_flag) show-fallback @endif
                    fallback-color="danger"
                    fallback-label="Flagged For Deletion"
                />
                <x-filament-action :action="$this->toggleManualHoldAction" />
            @endif
        </div>
        @if ($this->part->type->inPartsFolder())
            <div class="flex flex-wrap gap-2">
                <span class="font-bold text-lg">
                    Part Attributes:
                </span>
                <x-filament-action :action="$this->viewBasePartAction" />
                <x-filament-action
                    :action="$this->toggleIsPatternAction"
                    show-fallback
                    fallback-color="gray"
                    fallback-label="{{$this->part->is_pattern ? 'Printed' : 'Not Printed'}}"
                />
                <x-filament-action
                    :action="$this->toggleIsCompositeAction"
                    show-fallback
                    fallback-color="gray"
                    fallback-label="{{$this->part->is_composite ? 'Assembly' : 'Single Part'}}"
                />
                <x-filament-action
                    :action="$this->toggleIsDualMouldAction"
                    show-fallback
                    fallback-color="gray"
                    fallback-label=" {{$this->part->is_dual_mould ? 'Dual Moulded' : 'Single Mould'}}"
                />
            </div>
            @if ($this->viewRebrickableAction->isVisible() ||
                $this->viewBrickLinkAction->isVisible() ||
                $this->viewBricksetAction->isVisible() ||
                $this->viewBrickOwlAction->isVisible()
            )
                <div class="flex flex-wrap gap-2 place-items-center">
                    <div class="font-bold text-lg">
                        External Sites:
                    </div>
                    @if (!is_null($part->rebrickable_part))
                        <x-library-icon icon="link-on" class="w-5" color="fill-gray-400" title="External site data provided by Rebrickable.com" />
                    @else
                        <x-library-icon icon="link-off" class="w-5" color="fill-red-300" title="External site data provided by part keywords" />
                    @endif
                    <x-filament-action :action="$this->viewRebrickableAction" />
                    <x-filament-action :action="$this->viewBrickLinkAction" />
                    <x-filament-action :action="$this->viewBrickSetAction" />
                    <x-filament-action :action="$this->viewBrickOwlAction" />
                </div>
            @endif
        @endif
        <div class="w-full p-4 border border-gray-200 rounded-md">
          <div class="flex flex-col md:flex-row-reverse w-full">
            <div class="flex w-full justify-center items-center md:w-1/3">
                <img class = 'w-80 h-80 object-contain'
                @if(!$part->isTexmap()) wire:click="$dispatch('open-modal', { id: 'ldbi' })" @endif
                src="{{$part->getFirstMediaUrl('image')}}" alt="{{ $part->description }}" title="{{ $part->description }}">
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
            <div class="text-lg font-bold">Status:</div>
            <x-part.status :$part show-status />
            @if($part->check_messages->hasIssues())
                <x-message.not-releaseable :$part />
            @endif
        @if($part->isUnofficial())
            <div class="text-md font-bold">Current Votes:</div>
            <x-vote.table :votes="$part->votes" />
            @if (count($part->missing_parts ?? []) > 0)
                <div class="text-md font-bold">Missing Part References:</div>
                @foreach($part->missing_parts as $missing)
                    <div class="text-red-500">{{ $missing }}</div>
                @endforeach
            @endif
            <livewire:tables.part-dependencies-table :$part :dependency="\App\Enums\PartDependency::Parents" :library="\App\Enums\PartLibrary::Unofficial" />
            <livewire:tables.part-dependencies-table :$part :dependency="\App\Enums\PartDependency::Subparts" :library="\App\Enums\PartLibrary::Unofficial" />
            <x-accordion id="officialParts">
                <x-slot name="header" class="text-md font-bold">
                    Official parents and subparts
                </x-slot>
                <livewire:tables.part-dependencies-table :$part :dependency="\App\Enums\PartDependency::Parents" :library="\App\Enums\PartLibrary::Official" />
                <livewire:tables.part-dependencies-table :$part :dependency="\App\Enums\PartDependency::Subparts" :library="\App\Enums\PartLibrary::Official" />
            </x-accordion>
        @else
            <livewire:tables.part-dependencies-table :$part :dependency="\App\Enums\PartDependency::Parents" :library="\App\Enums\PartLibrary::Official" />
            <livewire:tables.part-dependencies-table :$part :dependency="\App\Enums\PartDependency::Subparts" :library="\App\Enums\PartLibrary::Official" />
            <x-accordion id="unofficialParents">
                <x-slot name="header" class="text-md font-bold">
                    Unofficial parents
                </x-slot>
                <livewire:tables.part-dependencies-table :$part :dependency="\App\Enums\PartDependency::Parents" :library="\App\Enums\PartLibrary::Unofficial" />
            </x-accordion>
        @endif
        <div class="text-lg font-bold">Part Events:</div>
        <div class="flex flex-col space-y-4">
            @if ($part->isOfficial() || !is_null($part->official_part))
                <x-accordion id="archiveEvents">
                    <x-slot name="header">
                        Archived Part Events:
                    </x-slot>
                    @if($part->isUnofficial())
                        <livewire:tables.part-events-table :part="$part->official_part->load('events')" />
                    @else
                        <livewire:tables.part-events-table :$part />
                    @endif
               </x-accordion>
            @endif
            @if ($part->isUnofficial())
                @forelse ($part->orderedEvents()->unofficial() as $event)
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
                    <x-filament::button type="submit" class="mt-2">
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
        <div class="flex flex-col space-y-2">
            <div class="flex gap-2">
                <x-3d-viewer.button.normal />
                <x-3d-viewer.button.harlequin />
                <x-3d-viewer.button.bfc />
                <x-3d-viewer.button.studlogo />
                <x-3d-viewer.button.showaxis />
                <x-3d-viewer.button.photo />
            </div>
            <div class="flex flex-col w-full h-full">
                <x-3d-viewer class="border border-gray-200 w-full h-[80vh]" partname="{{str_replace('\\', '/', $part->meta_name)}}" modelid="{{$part->id}}"/>
            </div>
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
