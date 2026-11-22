<div>
    <x-slot:title>LDraw.org Official Model Repository - Add Model</x-slot>
    <div class="flex flex-col space-y-2">
        <div class="text-2xl font-bold">
            Add Model
        </div>
        @if(($checks['errors'] ?? []) !== [])
            <x-message compact icon type="error">
                <x-slot:header>
                    The model file has errors
                </x-slot:header>
                <ul>
                    @foreach($checks['errors'] as $error)
                        <li wire:key="model-error-{{ $loop->iteration }}">{{ $error }}</li>
                    @endforeach
                </ul>
            </x-message>
        @endif
        @if(($checks['warnings'] ?? []) !== [])
            <x-message compact icon type="warning">
                <x-slot:header>
                    Check the following before adding the model
                </x-slot:header>
                <ul>
                    @foreach($checks['warnings'] as $warning)
                        <li wire:key="model-warning-{{ $loop->iteration }}">{{ $warning }}</li>
                    @endforeach
                </ul>
            </x-message>
        @endif
        <form wire:submit="create" class="flex flex-col space-y-2">
            {{ $this->form }}

            <div class="flex gap-2">
                <x-filament::button type="submit">
                    <x-filament::loading-indicator wire:loading wire:target="create" class="h-5 w-5" />
                    Add Model
                </x-filament::button>
                @if($checks !== [] && ($checks['errors'] ?? []) === [])
                    <x-filament::button color="gray" wire:click="preview">
                        <x-filament::loading-indicator wire:loading wire:target="preview" class="h-5 w-5" />
                        3D View
                    </x-filament::button>
                @endif
            </div>
        </form>
    </div>
    <x-filament::modal id="ldbi" alignment="center" width="7xl">
        <x-slot name="heading">
            3D View
        </x-slot>
        <x-3d-viewer class="border border-gray-200 rounded-lg overflow-hidden w-full h-[calc(100dvh-12rem)]" model-type="user" main-model-name="model.ldr" />
    </x-filament::modal>
</div>
