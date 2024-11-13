<x-layout.tracker>
    <x-slot:title>{{$release->name}} New Parts Preview</x-slot>
    <div class="text-lg font-bold">{{$release->name}} New Parts Preview</div>
    <div class="flex flex-col space-y-2">
        @isset($release->part_list) 
            @foreach($release->part_list as list($description, $filename))
                    <div class="rounded border w-fit">
                        <div class="flex flex-row space-x-2 items-center font-bold bg-gray-200 p-2">
                            <div>
                                {{$filename}} - {{$description}}
                            </div>
                            <a class="rounded p-2 my-1 border border-gray-950 bg-gray-100" href="{{route('parts.show', App\Models\Part\Part::official()->where('filename', $filename)->first())}}">
                                View Part
                            </a>
                        </div>
                        <img class="p-2" src="{{asset('images/library/updates/view' . $release->short . '/' . substr($filename, 0, -4) . '.png')}}">
                    </div>
            @endforeach  
        @else
            <p>
                No parts in this release or preview has not been generated for this release
            </p>
        @endisset
    </div>
</x-layout.tracker>