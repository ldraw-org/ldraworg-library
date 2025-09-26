<x-layout.tracker>
    <x-slot:title>{{$release->name}} New Parts Preview</x-slot>
    <div class="text-lg font-bold">{{$release->name}} New Parts Preview</div>
    <div class="flex flex-col space-y-2">
        @forelse($release->getMedia('view') as $part)
            <div class="rounded border border-gray-200 w-fit">
                <div class="flex flex-row space-x-2 items-center font-bold bg-gray-200 p-2">
                    <div>
                        {{$part->getCustomProperty('filename')}} - {{$part->getCustomProperty('description')}}
                    </div>
                    <a class="rounded p-2 my-1 border border-gray-950 bg-gray-100" href="{{route('parts.show', $part->getCustomProperty('id'))}}">
                        View Part
                    </a>
                </div>
                <img class="p-2" src="{{$part->getUrl()}}">
            </div>
        @empty
            <p>
                No parts in this release or preview has not been generated for this release
            </p>
        @endforelse
    </div>
</x-layout.tracker>