<div class="flex flex-col space-y-2">
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
                Check the following before saving the model
            </x-slot:header>
            <ul>
                @foreach($checks['warnings'] as $warning)
                    <li wire:key="model-warning-{{ $loop->iteration }}">{{ $warning }}</li>
                @endforeach
            </ul>
        </x-message>
    @endif
</div>
