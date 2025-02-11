<x-slot:title>
    LDraw Member Poll: {{$poll->title}}
</x-slot>
<x-slot:breadcrumbs>
<x-breadcrumb-item class="active" item="LDraw Member Poll: {{$poll->title}}" />
</x-slot>

<div>
    @if (!$poll->enabled)
        This poll is not active
    @else
        <form wire:submit="castVote">
            {{$this->form}}
            <x-filament::button type="submit">
                <x-filament::loading-indicator wire:loading wire:target="castVote" class="h-5 w-5" />
                Vote
            </x-filament::button>
        </form>
    @endif
</div>
