@props(['part', 'show_obsolete' => false])
<div>
    @if(stripos($part->description, "obsolete") === false || $show_obsolete)
        <a href="{{route('parts.show', $part)}}">
    @endif
            <div @class([
                'flex flex-col rounded border border-gray-200 h-full',
                'bg-red-100' => $part->isObsolete(),
                'bg-green-100' => $part->isOfficial() && !$part->isObsolete(),
                'bg-yellow-100' => $part->isUnofficial() && !$part->isObsolete()
            ])>
                <div class="bg-gray-200 font-bold p-2">
                    {{basename($part->filename, '.dat')}}
                </div>
                @if(stripos($part->description, "obsolete") === false || $show_obsolete)
                    <img class="p-2 object-scale-down max-h-[150px]" src="{{$part->getFirstMediaUrl('image')}}" title="{{$part->description}}" alt="{{$part->description}}" loading="lazy">
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
    @if(stripos($part->description, "obsolete") === false || $show_obsolete)
        </a>
    @endif
</div>
