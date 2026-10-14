@props([])
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
