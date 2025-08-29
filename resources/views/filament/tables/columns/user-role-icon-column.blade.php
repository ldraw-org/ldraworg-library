<div class="flex" {{ $getExtraAttributeBag() }}>
    @if($getRecord()->hasRole('Library Admin'))
        <x-library-icon icon="user-library-admin" class="w-7" title="Part Library Admin"/>
    @elseif($getRecord()->hasRole('Senior Reviewer'))
        <x-library-icon icon="user-senior-reviewer" class="w-7" title="Senior Part Reviewer"/>
    @elseif($getRecord()->hasRole('Part Header Editor'))
        <x-library-icon icon="user-header-editor" class="w-7" title="Part Header Editor"/>
    @elseif($getRecord()->hasRole('Part Reviewer'))
        <x-library-icon icon="user-part-reviewer" class="w-7" title="Part Reviewer"/>
    @endif
    @if($getRecord()->hasRole('Part Author'))
        <x-library-icon icon="user-part-author" class="w-7" title="Part Author"/>
    @endif
    @if($getRecord()->hasRole('OMR Author'))
        <x-library-icon icon="viewer-stud-logo" class="w-7" title="OMR Author"/>
    @endif
</div>
