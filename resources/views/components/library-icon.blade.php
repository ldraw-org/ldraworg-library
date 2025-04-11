@if(!$lowerLeftIcon && !$lowerRightIcon)
    <x-dynamic-component :component="$icon" {{ $attributes->merge(['class' => $color]) }} />
@else
    <div {{ $attributes }}>
        <div class="relative">
            <x-dynamic-component :component="$icon" class="{{ $color }}"/>
            @if($lowerLeftIcon)
                <x-dynamic-component :component="$lowerLeftIcon" class="absolute bottom-0 left-0 w-1/3 {{$lowerLeftColor}}" />
            @endif
            @if($lowerRightIcon)
                <x-dynamic-component :component="$lowerRightIcon" class="absolute bottom-0 right-0 w-1/3 {{$lowerRightColor}}" />
            @endif
        </div>
    </div>
@endif