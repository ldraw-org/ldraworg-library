@props(['part'])
<div >
    @if(stripos($part->description, "obsolete") === false)
        <a href="{{route(($part->isUnofficial() ? 'tracker.show' : 'official.show'), $part)}}">
    @endif
            <div @class([
                'flex flex-col rounded border h-full',
                'bg-red-200' => stripos($part->description, "obsolete") !== false,      
                'bg-green-200' => !$part->isUnofficial() && (stripos($part->description, "obsolete") === false), 
                'bg-yellow-200' => $part->isUnofficial() && (stripos($part->description, "obsolete") === false) 
            ])>
                <div class="bg-gray-200 font-bold p-2">
                    {{basename($part->filename, '.dat')}}
                </div>
                <img class="p-2" src="{{version('images/library/' . $part->libFolder() . '/' . substr($part->filename, 0, -4) . '.png')}}" title="{{$part->description}}" alt="{{$part->description}}">
                @if(stripos($part->description, "obsolete") === false)
                    <p class="text-sm p-2">{{$part->description}}</p>
                    @if($part->isUnofficial())
                        <div class="p-2">
                            <x-part.status :$part show-status />
                        </div>
                    @endif
                @else
                    <p class="p-2">Obsolete file</p>
                @endif
            </div>
    @if(stripos($part->description, "obsolete") === false)
        </a>
    @endif
</div>