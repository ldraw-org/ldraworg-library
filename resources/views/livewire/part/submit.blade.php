<x-slot:title>
     Parts Tracker File Submit Form
</x-slot>
<div>
    <div class="text-2xl font-bold">
        Parts Tracker File Submit Form
    </div>
    <div wire:key="fileStates-wrapper">
        @if(count($fileStates) > 0)
            <div wire:key='fileStates-list' class="flex flex-col relative p-2 my-2 space-y-2 border border-gray-200 rounded-lg">
                <h2 class="absolute top-0 left-2 transform -translate-y-1/2 bg-white px-2 text-md font-semibold text-gray-700">
                    There were file errors/warnings
                </h2>
                @foreach($fileStates as $filename => $state)
                    <div wire:key="file-errors-{{ $filename }}">
                        <div class="font-bold">{{ $filename }}</div>
                        <x-message.submit-validation filename="{{$filename}}" :$state />
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
        $wire.on('FilePond:processfile', (e) => {
            $wire.checkFile(e.file.filename);
        });
        $wire.on('FilePond:removefile', (e) => {
            $wire.removeFile(e.file.filename);
        });

        $wire.on('setFileState', (params) => {
            let fileInput = null;
            console.log(params);
            const status = params.state ? 'processing-complete' : 'error';
            const el = [...document.querySelectorAll('.filepond--file-info-main')]
                .find(e => e.textContent.trim() === params.filename);
            console.log(el);
            if (el) {
                fileInput = el.closest('.filepond--item');
                console.log(fileInput);
            }

            if (fileInput) {
                fileInput.dataset.filepondItemState = status;
            }
        });
    </script>
    @endscript
</div>
