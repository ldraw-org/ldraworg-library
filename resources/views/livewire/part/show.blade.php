@use("\App\Enums\LibraryIcon")
@use("\App\Enums\PartDependency")
@use("\App\Enums\PartLibrary")

<div class="flex flex-col space-y-4 bg-white p-2 rounded-lg" x-data="{ webgl: true }">
    <x-slot:title>
        File Detail {{ $part->filename }}
    </x-slot>
    <div class="flex flex-row w-full">
        <x-filament-action-group :group="$this->topMenuActionGroup()" />
        @if (!$part->isTexmap() && $webGlSupported)
            <x-filament::button
                color="gray"
                outlined
                wire:click="$dispatch('open-modal', { id: 'ldbi' })"
            >
                3D View
            </x-filament::button>
        @endif
        <x-filament-action class="ml-auto" :action="$this->deleteAction()" />
    </div>
    <div>
        <div class="flex flex-row space-x-2 place-items-center">
            <div @class([
                'text-3xl font-bold py-2 px-3 w-fit rounded-lg',
                'bg-yellow-100' => $part->isUnofficial(),
                'bg-green-100' => $part->isOfficial(),
            ]) >
                {{ucfirst($part->libFolder())}} File {{ $part->filename }}
            </div>
            <x-filament-action :action="$this->trackPartAction" />
        </div>
        @if ($part->isUnofficial())
            <div class="flex flex-row space-x-2 text-sm place-items-center">
                <x-library-icon :icon="LibraryIcon::Alert" class="w-5" color="fill-yellow-800" />
                <div>
                    Warning: Model breaking changes to unofficial parts may occur anytime prior to release
                </div>
            </div>
        @endif
    </div>


    <div class="flex flex-row space-x-2">
        @if (!is_null($part->rebrickable_part))
            <x-library-icon :icon="LibraryIcon::LinkOn" class="w-6" color="fill-gray-400" title="External site data provided by Rebrickable.com" />
        @else
            <x-library-icon :icon="LibraryIcon::LinkOff" class="w-6" color="fill-red-300" title="External site data provided by part keywords" />
        @endif
        <x-filament-action-group  :group="$this->externalSiteActionGroup()" />
    </div>
    <x-labelled-section>
        <div class="flex flex-row space-x-2">
            <x-filament-action-group :group="$this->partOperationsActionGroup()" />
        </div>
        <div>
            <img
                class = 'w-fit object-contain bg-gray-50 rounded-lg md:float-right md:ml-4 mb-2 p-2'
                src="{{ file_exists($part->getFirstMediaPath('image')) ? $part->getFirstMediaUrl('image') : $part->getFallbackMediaUrl('image')}}"
                alt="{{ $part->description }}"
                title="{{ $part->description }}"
            >
            <div>
                <pre class="whitespace-pre-wrap wrap-break-word font-mono"><code>{{ $part->header }}</code></pre>
            </div>
        </div>

        <x-accordion id="showContents">
            <x-slot name="header">
                Show file code
            </x-slot>
            <div>
                <pre><code class="whitespace-pre-wrap wrap-break-word font-mono">{{ trim($part->body->body) }}</code></pre>
            </div>
        </x-accordion>
    </x-labelled-section>
    <x-labelled-section>
        <x-slot name="header">
            Status
        </x-slot>
        <x-part.status :$part show-status />
        @if ($part->isUnofficial())
            <x-vote.list :votes="$part->votes" />
        @endif
        @if($part->check_messages->hasIssues())
            <div class="flex flex-col space space-y-2">
                <x-message.checks :$part />
                @if (count($part->missing_parts ?? []) > 0)
                    <div class="text-md font-bold">Missing Part References:</div>
                    @foreach($part->missing_parts as $missing)
                        <div class="text-red-500">{{ $missing }}</div>
                    @endforeach
                @endif
            </div>
    @endif
    </x-labelled-section>
    <x-labelled-section>
        <x-slot name="header">
            Part Dependencies
        </x-slot>
        @if ($part->isUnofficial())
            <livewire:tables.part-dependencies-table :$part :dependency="PartDependency::Parents" :library="PartLibrary::Unofficial" />
            <livewire:tables.part-dependencies-table :$part :dependency="PartDependency::Subparts" :library="PartLibrary::Unofficial" />
            <x-accordion id="officialParts">
                <x-slot name="header" class="text-md font-bold">
                    Official parents and subparts
                </x-slot>
                <livewire:tables.part-dependencies-table :$part :dependency="PartDependency::Parents" :library="PartLibrary::Official" />
                <livewire:tables.part-dependencies-table :$part :dependency="PartDependency::Subparts" :library="PartLibrary::Official" />
            </x-accordion>
        @else
            <livewire:tables.part-dependencies-table :$part :dependency="PartDependency::Parents" :library="PartLibrary::Official" />
            <livewire:tables.part-dependencies-table :$part :dependency="PartDependency::Subparts" :library="PartLibrary::Official" />
            <x-accordion id="unofficialParents">
                <x-slot name="header" class="text-md font-bold">
                    Unofficial parents
                </x-slot>
                <livewire:tables.part-dependencies-table :$part :dependency="PartDependency::Parents" :library="PartLibrary::Unofficial" />
            </x-accordion>
        @endif
    </x-labelled-section>
    <x-labelled-section padded="0">
        <x-slot name="header">
            Part Events
        </x-slot>
        @if ($part->isUnofficial())
            <div class="flex flex-col divide-y divide-gray-200">
                @forelse ($part->orderedEvents()->unofficial() as $event)
                    <x-event.list-item class="p-2" :$event wire:key="part-event-{{$event->id}}" />
                @empty
                    <div>No Events</div>
                @endforelse
            </div>
        @endif
        @if ($part->isOfficial() || !is_null($part->official_part))
            @if ($part->isUnOfficial())
            <x-accordion id="archiveEvents">
                <x-slot name="header">
                    Archived Part Events:
                </x-slot>
                <livewire:tables.part-events-table :part="$part->official_part->load('events')" />
            </x-accordion>
            @else
                <livewire:tables.part-events-table :$part />
            @endif
         @endif
    </x-labelled-section>
    @if ($part->isUnofficial())
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
                <x-3d-viewer class="border border-gray-200 w-full h-[80vh]" partname="{{str_replace('\\', '/', $part->meta_name)}}" modelid="{{$part->id}}" />
            </div>
        </div>
    </x-filament::modal>
    <x-filament-actions::modals />
    @push('scripts')
        @script
        <script type="text/javascript">
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');

            if (gl && gl instanceof WebGLRenderingContext) {
                $wire.set('webGlSupported', true);
            }

            $wire.on('open-modal', () => {
                if (scene == null) {
                    $wire.dispatch('ldbi-render-model');
                }
            });
        </script>
        @endscript
    @endpush
</div>
