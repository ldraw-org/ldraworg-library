@props(['title', 'image', 'link' => null])

<div {{$attributes->merge(['class' => 'flex flex-col border border-gray-200 rounded-lg p-2'])}}>
    @isset($link)
        <a href="{{$link}}">
    @endisset
    <img class="object-scale-down" src="{{$image}}" />
    <div class="text-xl font-bold">
        {{$title}}
    </div>
    <div class="text-wrap break-words">
        <p>
            {{$slot}}
        </p>
    </div>
    @isset($link)
        </a>
    @endisset
</div>
