@php
    $state = $getRecord();
    if ($state instanceof \App\Models\Part) {
        $part = $getRecord();
    } else {
        $part = $state?->part;
    }
@endphp
<div>
    @if (!is_null($part))
        <x-part.status :$part show-status />
    @else
        Part Removed
    @endif
</div>
