@props(['release'])
<div class="flex flex-col rounded border border-gray-200 divide-y w-fit">
    <div class="flex flex-row items-center">
        <div class="font-bold justify-self-start p-2">
            LDraw.org Parts Update {{$release->name}}
        </div>
        @if (\Storage::disk('images')->exists('updates/' . $release->short . '.png'))
            <img class="w-1/3 justify-self-end p-2" src="{{asset('images/updates/' .  $release->short . '.png')}}"/>
        @else
            <img class="w-1/3 justify-self-end p-2" src="{{asset('images/updates/default.png')}}"/>
        @endif
    </div>
    <div class="flex flex-row divide-x">
        <div class="flex flex-col justify-items-start">
            <div class="font-bold p-2">
                Release Notes
            </div>
            <div class="p-2">
                {!! nl2br($release->notes) !!}
            </div>
            <div class="p-2">
                <a class="font-bold hover:underline text-blue-600 hover:text-blue-800 visited:text-purple-600" href="{{route('part-update.view', $release)}}">
                    Preview Parts in Update
                </a>
                <p>
                    (graphics-intensive page)
                </p>
            </div>
        </div>
        <div class="flex flex-col justify-items-start *:p-2">
            <div class="font-bold">
                Download Links
            </div>
            @if ($release->isLatest())
                <a class="hover:underline text-blue-600 hover:text-blue-800 visited:text-purple-600" data-pan="update-complete-zip-{{$release->short}}" href="{{asset('library/updates/complete.zip')}}">
                    Complete LDraw.org Library Zip archive (complete.zip)
                </a>
                @if(Storage::disk('library')->exists('updates/LDrawParts.exe'))
                <a class="hover:underline text-blue-600 hover:text-blue-800 visited:text-purple-600" data-pan="update-complete-exe-{{$release->short}}" href="{{asset('library/updates/LDrawParts.exe')}}">
                    Complete LDraw.org Library Windows installer (LDrawParts.exe)
                </a>
                @else
                    <div class="p-2">
                        Complete LDraw.org Library Windows installer (coming soon)
                    </div>
                @endif
            @endif
            <a class="hover:underline text-blue-600 hover:text-blue-800 visited:text-purple-600" data-pan="update-zip-{{$release->short}}" href="{{asset('library/updates/lcad' . $release->short . '.zip')}}">
                Zip archive of the updated files ({{$release->short}}.zip)
            </a>
        </div>
    </div>
</div>
