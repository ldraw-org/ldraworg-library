<x-slot:title>
     Parts Tracker File Submit Form
</x-slot>
<div>
    <div class="text-2xl font-bold">
        Parts Tracker File Submit Form
    </div>
    <div wire:key="fileStates-wrapper">
        @if(count($this->filesWithMessages) > 0)
            <div wire:key='fileStates-list' class="flex flex-col relative p-2 my-2 space-y-2 border border-gray-200 rounded-lg">
                <h2 class="absolute top-0 left-2 transform -translate-y-1/2 bg-white px-2 text-md font-semibold text-gray-700">
                    There were file errors/warnings
                </h2>
                @foreach($this->filesWithMessages as $key => $state)
                    <div wire:key="file-errors-{{ $key }}">
                        <div class="font-bold">{{ $state['filename'] }}</div>
                        <x-message.submit-validation :filename="$state['filename']" :$state />
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <form wire:submit="create">
        {{ $this->form }}

        <x-filament::button type="submit">
            <x-filament::loading-indicator wire:loading class="h-5 w-5" />
            Submit
        </x-filament::button>
    </form>
    <x-filament::modal id="post-submit" width="5xl" :close-by-clicking-away="false" :close-button="false">
        <x-slot name="heading">
            Submit Successful
        </x-slot>
        <p>
            The following files passed validation checks and have been submitted to the Parts Tracker
        </p>
        <livewire:tables.submitted-parts-table :parts="$submitted_parts" />
        <p>
            The following files were rejected:
        </p>
        <div>
            @empty($rejected_files)
              None
            @else
                {{ $rejected_files }}
            @endempty
        </div>
        <x-filament::button @click="$dispatch('close-modal', { id: 'post-submit' })">
            Ok
        </x-filament::button>
    </x-filament::modal>

    @script
    <script type="text/javascript">
        // The server validates each file as Filament finishes uploading it; this only colours the FilePond items.
        // FilePond's own error state discards the file, so the state attribute is set directly to keep the
        // file submittable once the replace/fix toggles clear its error.
        let pond = null;

        const paintFileStates = () => {
            for (const file of pond?.getFiles() ?? []) {
                const state = $wire.fileStates[file.serverId];
                const item = document.getElementById(`filepond--item-${file.id}`);

                if (state && item) {
                    item.dataset.filepondItemState = state.hasErrors ? 'error' : 'processing-complete';
                }
            }
        };

        $wire.on('FilePond:processfile', (detail) => {
            pond = detail.pond;
            paintFileStates();
        });

        $wire.$watch('fileStates', paintFileStates);
    </script>
    @endscript
</div>
