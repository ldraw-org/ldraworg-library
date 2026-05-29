<div>
    <form class="space-y-2" method="POST" wire:submit="login">
        {{ $this->form }}
        <x-filament::button type="submit">
            <x-filament::loading-indicator wire:loading class="h-5 w-5" />
            Submit
        </x-filament::button>
    </form>
    <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="{{ route('password.request') }}">Forgot Password</a>
</div>
