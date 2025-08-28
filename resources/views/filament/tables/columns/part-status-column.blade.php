<div {{ $getExtraAttributeBag() }}>
    @if ($hasPart())
        <x-part.status :part="$getPart()" show-my-vote />
    @else
        Part Removed
    @endif
</div>
