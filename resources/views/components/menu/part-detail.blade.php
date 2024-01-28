@props(['part'])
<x-menu>
    <x-menu.item label="Download" link="{{route($part->isUnofficial() ? 'unofficial.download' : 'official.download', $part->filename)}}" />
    @if(Auth::check() && $part->isUnofficial() && (Auth::user()->can('create', [\App\Models\Vote::class, $part]) || Auth::user()->can('update', [$part->votes()->firstWhere('user_id', Auth::user()->id)])))
        <x-menu.item label="Review/Comment" x-on:click="document.getElementById('voteForm').scrollIntoView()" />   
    @endif
    @if($part->hasPatterns() || $part->hasComposites() || $part->hasStickerShortcuts())
        <x-menu.item label="View Patterns/Shortcuts" link="{{route('search.suffix', ['s' => $part->basepart()])}}" />
    @endif
    @canany(['part.edit.header','part.own.edit.header','part.edit.number','part.delete'])
        <x-menu.dropdown dropdown label="Admin Tools">
            @if($part->isUnofficial() && $part->type->folder == 'parts/' && $part->descendantsAndSelf->where('vote_sort', '>', 2)->count() == 0)
                @can('vote.admincertify')
                    <x-menu.item label="Admin Certify All" link="{{route('tracker.vote.adminquickvote', $part)}}" />
                @endcan
            @endif
            @if($part->isUnofficial() && $part->type->format == 'dat')
                @canany(['part.edit.header', 'part.own.edit.header'])
                    <x-menu.item label="Edit Header" link="{{route('tracker.edit', $part)}}" />
                @endcanany
            @endif
            @can('part.edit.header')
                <x-menu.item label="Regenerate Image" wire:click="updateImage" />
                <x-menu.item label="Regenerate Subpart List" wire:click="updateSubparts" />
            @endcan  
            @can('part.edit.number')
                <x-menu.item label="Renumber" link="{{route('tracker.move.edit', $part)}}" />
            @endcan
            @if($part->isUnofficial())
                @can('part.delete')
                    <x-menu.item label="Delete" wire:click="$dispatch('open-modal', { id: 'delete-part' })" />
                @endcan
            @endif
        </x-menu.item>
    @endcanany
    <x-menu.item label="3D View" x-show="webgl" x-cloak wire:click="$dispatch('open-modal', { id: 'ldbi' })" />
</x-menu>
