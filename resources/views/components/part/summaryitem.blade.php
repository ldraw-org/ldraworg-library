@props(['part'])
<div>
    <a href="{{route(('parts.show'), $part)}}">
        <div @class([
            'flex flex-col rounded border h-full',
            'bg-green-200' => $part->isOfficial(),
            'bg-yellow-200' => $part->isUnofficial()
        ])>
            <div class="bg-gray-200 font-bold p-2">
                {{basename($part->filename, '.dat')}}
            </div>
            <img class="p-2 object-scale-down max-h-[150px]" src="{{version("images/library/{$part->imagePath()}")}}" title="{{$part->description}}" alt="{{$part->description}}">
            <p class="text-sm p-2">{{$part->description}}</p>
            @if($part->isUnofficial())
                <div class="p-2">
                    <x-part.status :$part show-status />
                </div>
            @endif
        </div>
    </a>
</div>
