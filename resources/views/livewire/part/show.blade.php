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
            <x-filament-action action="webglViewAction" />

            @if ($this->editHeaderAction->isVisible() ||
                $this->editNumberAction->isVisible() ||
                $this->editBasePartAction->isVisible() ||
                $this->updateImageAction->isVisible() ||
                $this->recheckPartAction->isVisible() ||
                $this->updateSubpartsAction->isVisible() ||
                $this->retieFixAction->isVisible() ||
                $this->deleteAction->isVisible()
            )

                <x-filament-actions::group
                    :actions="[
                        $this->editHeaderAction,
                        $this->editNumberAction,
                        $this->editBasePartAction,
                        $this->updateImageAction,
                        $this->recheckPartAction,
                        $this->updateSubpartsAction,
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
                'bg-green-100' => !$part->isUnofficial(),
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
        <div>
            <span class="font-bold text-lg">
                External Sites:
            </span>
             <x-filament-action action="viewRebrickableAction" />
            <x-filament-action action="viewBricklinkAction" />
            <x-filament-action action="viewBrickowlAction" />
        </div>
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
            @if (!$part->can_release)
                <x-message compact icon type="warning">
                    <x-slot:header>
                        This part is not releaseable
                    </x-slot:header>
                    <ul>
                        @foreach($part->part_check_messages['errors'] as $error)
                            @if($error == "Has uncertified subfiles")
                                <x-accordion id="showContents">
                                    <x-slot name="header">
                                        <li>{{$error}}</li>
                                    </x-slot>
                                    <div class="px-4">
                                        @foreach($part->descendants->where('vote_sort', '!=', '1') as $p)
                                            <a href="{{route('parts.show', $p)}}" class="underline decoration-dotted hover:decoration-solid hover:text-gray-500">{{$p->filename}} ({{$p->statusText()}})</a><br/>
                                        @endforeach
                                    </div>
                                </x-accordion>
                            @else
                                <li>{{$error}}</li>
                            @endif
                        @endforeach
                    </ul>
                </x-message>        
            @endif
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
        @endif
        <div class="text-lg font-bold">Part Events:</div>
        <div class="flex flex-col space-y-4">
            @if (!$part->isUnofficial() || !is_null($part->official_part))
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
            @can('vote', [\App\Models\Vote::class, $this->part])
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
            <div id="ldbi-container" class="border w-full h-[80vh]"> 
                <canvas id="ldbi-canvas" class="size-full"></canvas>
            </div>
        </div>
    </x-filament::modal>
    <x-filament-actions::modals />
    @push('scripts')
        <x-layout.ldbi-scripts />
        <script type="text/javascript">
            var scene;
        </script>    
        @script
        <script>
            let part_id = {{$part->id}};
            var part_paths;


            LDR.Options.bgColor = 0xFFFFFF;

            LDR.Colors.envMapPrefix = '/assets/ldbi/textures/cube/';    
            LDR.Colors.textureMaterialPrefix = '/assets/ldbi/textures/materials/';

            $wire.on('open-modal', (modal) => {
                let idToUrl = function(id) {
                    if (part_paths[id]) {
                        return [part_paths[id]];
                    }
                    else {
                        return [id];
                    }
                };

                let idToTextureUrl = function(id) {
                    if (part_paths[id]) {
                        return part_paths[id];
                    }
                    else {
                        return id;
                    }
                };
                if (modal.id == 'ldbi' && WEBGL.isWebGLAvailable() && !scene) {
                    // pre-fetch the paths to the subfiles used to speed up loading
                    fetch('/ldbi/part/' + part_id)
                        .then(response => response.json())
                        .then((response) => {
                            part_paths = response;
                            LDR.Colors.load(() => {
                                scene = new LDrawOrg.Model(
                                    document.getElementById('ldbi-canvas'), 
                                    document.getElementById('filename').innerHTML.replace(/^(parts\/|p\/)/, ''),
                                    {idToUrl: idToUrl, idToTextureUrl: idToTextureUrl}
                                );
                                window.addEventListener('resize', () => scene.onChange());
                            },() => {},part_paths['ldconfig.ldr']);
                            
                        })
                }
            });

            $wire.on('ldbi-default-mode', () => {
                scene.default_mode();
            });

            $wire.on('ldbi-harlequin-mode', () => {
                scene.harlequin_mode();
            });

            $wire.on('ldbi-bfc-mode', () => {
                scene.bfc_mode();
            });

            $wire.on('ldbi-stud-logos', () => {
                if (LDR.Options.studLogo == 1) {
                    LDR.Options.studLogo = 0;
                } else {
                    LDR.Options.studLogo = 1;
                }
                scene.reload();
            });

            $wire.on('ldbi-show-origin', () => {
                scene.axesHelper.visible = !scene.axesHelper.visible;
                scene.reload();
            });

            $wire.on('ldbi-physical-mode', () => {
                if (scene.loader.physicalRenderingAge > 0) {
                    scene.setPhysicalRenderingAge(0);
                }
                else {
                    scene.setPhysicalRenderingAge(20);
                }
            });
        </script>
        @endscript
    @endpush
</div>
