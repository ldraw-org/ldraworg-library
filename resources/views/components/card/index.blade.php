@props([
    'title',
    'image' => null,
    'link' => null,
])

@php
    $tag = $link ? 'a' : 'div';
@endphp

<{{$tag}}
    {{ $attributes->merge([
        'class' => '
            flex flex-col
            border border-gray-200
            rounded-lg
            bg-white
            transition
            hover:border-gray-300
            hover:shadow-sm
            w-fit
            p-2
        '
    ]) }}
    @if($link)
    href="{{ $link }}"
@endif
>

@if($image)
    <img
        src="{{ $image }}"
        alt="{{ $title }}"
        class="mb-3 self-center object-scale-down block"
    >
@endif

<div class="text-xl font-semibold mb-2">
    {{ $title }}
</div>

<div class="text-sm text-gray-600 leading-relaxed">
    {{ $slot }}
</div>

</{{$tag}}>
