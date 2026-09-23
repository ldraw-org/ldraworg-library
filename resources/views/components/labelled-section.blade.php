@props(['header' => null, 'padded' => true])
<div {{ $attributes }} >
    @isset($header)
        <h3 class="font-bold text-black mb-3">{{ $header }}</h3>
    @endisset
    <div @class(['flex flex-col space-y-2 border border-gray-200 rounded-lg',
        'p-4' => $padded,
    ]) >
        {{ $slot }}
    </div>
</div>
