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
                <x-library-icon icon="error" class="w-14" color="fill-red-800" />
                @break
            @case('warning')
                <x-library-icon icon="alert" class="w-14" color="fill-yellow-800" />
                @break
            @case('info')
                <x-library-icon icon="info" class="w-14" color="fill-blue-800" />
                @break
        @endswitch
    @endif
    <div class="mx-2">
        <div class="font-bold">{{$header}}</div>
        <div>{{$slot ?? $message}}</div>
    </div>
</div>
