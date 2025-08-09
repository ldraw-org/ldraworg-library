<div x-data="{ webgl: true }">
    <x-slot:title>LDraw.org Official Model Repository - {{$set->name}}</x-slot>
    <x-slot:breadcrumbs>
      <x-breadcrumb-item class="active" item="Set Detail" />
    </x-slot>
    <div class="flex flex-col space-y-2">
        <div class="rounded border-gray-200 text-xl font-bold bg-gray-200 p-2">{{$set->number}} - {{$set->name}}</div>
        <div class="grid grid-cols-12 gap-2">
            <div class="col-span-8">
                <img class='object-scale-down' wire:click="openModal({{$set->mainModel()->id}})"
                src="{{version("images/omr/models/" . substr($set->mainModel()->filename(), 0, -4) . '.png')}}" alt="{{$set->number}} - {{$set->name}}" title="{{$set->number}} - {{$set->name}}">
            </div>
            <div class="flex flex-col col-span-4 space-y-2">
                <div class="rounded border border-gray-200 text-lg font-bold bg-gray-200 p-2">Models</div>
                @foreach($set->models->sortBy('alt_model') as $model)
                    <div class="flex flex-col rounded border">
                        <div class="font-bold bg-gray-200 p-2">
                            {{$model->alt_model_name ?? 'Main Model'}}
                        </div>
                        <div class="p-2">
                            <span class="font-bold pr-2">Author:</span>{{$model->user->author_string}}
                        </div>
                        <div class="grid grid-cols-3 p-2">
                            <div>
                                <span class="font-bold">Missing Parts</span><br>
                                {{$model->missing_parts ? 'Yes' : 'No'}}
                            </div>
                            <div>
                                <span class="font-bold">Missing Patterns</span><br>
                                {{$model->missing_patterns ? 'Yes' : 'No'}}
                            </div>
                            <div>
                                <span class="font-bold">Missing Stickers</span><br>
                                {{$model->missing_stickers ? 'Yes' : 'No'}}
                            </div>
                        </div>
                        @if ($model->notes['notes'] != '')
                            <div class="p-2">
                                <span class="font-bold pr-2">Notes:</span><br>
                                {{$model->notes['notes']}}
                            </div>
                        @endif
                        <div class="flex flex-row space-x-2">
                            <x-filament::button class="m-2 w-fit" wire:click="openModal({{$model->id}})">
                                3D View
                            </x-filament::button>
                            <a class="rounded-lg border border-gray-200 bg-blue-500 font-bold px-4 py-2 text-white m-2 w-fit" href="{{asset('library/omr/' . $model->filename())}}">Download</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <x-filament::modal id="ldbi" alignment="center" width="7xl" lazy>
        <x-slot name="heading">
            3D View
        </x-slot>
        <div class="flex flex-col space-y-2">
            <div class="flex gap-2">
                <x-3d-viewer.button.normal />
                <x-3d-viewer.button.studlogo />
                <x-3d-viewer.button.photo />
            </div>
            <div class="flex flex-col w-full h-full">
                <x-3d-viewer class="border border-gray-200 w-full h-[80vh]" modeltype="omr" />
            </div>
        </div>
    </x-filament::modal>
    @push('scripts')
        @script
        <script>
            $wire.on('open-modal', (modal) => {
                if (scene == null || modelid != $wire.model_id) {
                    if (scene) {
                        scene.clear();
                        scene = null;
                    }
                    modelid = $wire.model_id;
                    partname = $wire.model_name;
                    $wire.dispatch('ldbi-render-model');
                }
            });
        </script>
        @endscript
    @endpush

    </div>
