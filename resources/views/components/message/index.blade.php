<div @class([
    'p-4 border rounded-md flex items-center',
    'justify-center text-center' => $centered,
    'w-full' => !$compact,
    'w-fit' => $compact,
    'bg-red-100 text-red-800' => $type === 'error',
    'bg-yellow-100 text-yellow-800' => $type === 'warning',
    'bg-blue-100 text-blue-800' => $type === 'info',
])>
    @if($icon)
        @switch($type)
            @case('error')
                <x-mdi-close-octagon class="w-14 h-14 text-red-800" />
                @break
            @case('warning')
                <x-mdi-alert class="w-14 h-14 text-yellow-800" />
                @break
            @case('info')
                <x-mdi-information-slab-circle class="w-14 h-14 text-blue-800" />
                @break
        @endswitch
    @endif
    <div class="mx-2">
        <div class="font-bold">{{$header}}</div>
        <div>{{$slot ?? $message}}</div>
    </div>
</div>
