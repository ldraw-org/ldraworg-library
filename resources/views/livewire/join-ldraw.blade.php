<x-slot:title>
    Join LDraw.org
</x-slot>
<x-slot:menu>
    <x-menu.library />
</x-slot>

<div>
    @if ($meetsId)
        <form wire:submit="joinOrg">
            {{ $this->form }}
        </form>
    @else
        <p>
            You currently do not meet the identity verification requirements set by the LDraw.org Steering Committee.
            If you feel this is in error, please post in the forums Help section.
        </p>
    @endif
</div>
