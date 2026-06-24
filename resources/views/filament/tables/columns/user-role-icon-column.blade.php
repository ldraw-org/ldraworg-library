@use("\App\Enums\LibraryIcon")
<div class="flex" {{ $getExtraAttributeBag() }}>
    @if($getRecord()->hasRole('Library Admin'))
        <x-library-icon :icon="LibraryIcon::UserLibraryAdmin" class="w-7" title="Part Library Admin"/>
    @elseif($getRecord()->hasRole('Senior Reviewer'))
        <x-library-icon :icon="LibraryIcon::UserSeniorReviewer" class="w-7" title="Senior Part Reviewer"/>
    @elseif($getRecord()->hasRole('Part Header Editor'))
        <x-library-icon :icon="LibraryIcon::UserHeaderEditor" class="w-7" title="Part Header Editor"/>
    @elseif($getRecord()->hasRole('Part Reviewer'))
        <x-library-icon :icon="LibraryIcon::UserPartReviewer" class="w-7" title="Part Reviewer"/>
    @endif
    @if($getRecord()->hasRole('Part Author'))
        <x-library-icon :icon="LibraryIcon::UserPartAuthor" class="w-7" title="Part Author"/>
    @endif
    @if($getRecord()->hasRole('OMR Author'))
        <x-library-icon :icon="LibraryIcon::LegoBrick" class="w-7" title="OMR Author"/>
    @endif
</div>
