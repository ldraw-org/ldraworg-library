@props(['part'])
<div >
    @if(!$part->isObsolete())
        <a href="{{route(('parts.show', $part)}}">
    @endif
            <div @class([
                'flex flex-col rounded border border-gray-200 h-full',
                'bg-red-200' => $part->isObsolete(),
                'bg-green-200' => $part->isOfficial() && !$part->isObsolete(),
                'bg-yellow-200' => $part->isUnofficial() && !$part->isObsolete()
            ])>
                <div class="bg-gray-200 font-bold p-2">
                    {{basename($part->filename, '.dat')}}
                </div>
                <img class="p-2 object-scale-down max-h-[150px]" src="{{version("images/library/{$part->imagePath()}")}}" title="{{$part->description}}" alt="{{$part->description}}" loading="lazy">
                @if(stripos($part->description, "obsolete") === false)
                    <p class="text-sm p-2">{{$part->description}}</p>
                    @if($part->isUnofficial())
                        <div class="p-2">
                            <x-part.status :$part show-my-vote />
                        </div>
                    @endif
                @else
                    <p class="p-2">Obsolete file</p>
                @endif
            </div>
    @if(!$part->isObsolete())
        </a>
    @endif
</div>
