<x-layout.tracker>
    <x-slot:title>{{$release->name}} New Parts Preview</x-slot>
    <div class="text-lg font-bold">{{$release->name}} New Parts Preview</div>
    <div class="flex flex-col space-y-2">
        @isset($release->part_list) 
            @foreach($release->part_list as list($description, $filename))
                <a href="{{route('official.show', App\Models\Part::official()->where('filename', $filename)->first())}}">
                    <div class="rounded border w-fit">
                        <div class="font-bold bg-gray-200 p-2">
                            {{$filename}} - {{$description}}
                        </div>
                        <img class="p-2" src="{{asset('images/library/updates/view' . $release->short . '/' . substr($filename, 0, -4) . '.png')}}">
                    </div>
                </a>
            @endforeach  
        @else
            <p>
                No parts in this release or preview has not been generated for this release
            </p>
        @endisset
    </div>
</x-layout.tracker>