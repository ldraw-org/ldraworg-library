<x-3d-viewer class="border rounded-lg w-full h-full overflow-hidden" partname="{{$partname}}" modeltype="user" />

@push('scripts')
    @script
    <script>
        $wire.on('render-model', (event) => {
            parts = $wire.parts;
            $wire.dispatch('ldbi-render-model');
        });
    </script>
    @endscript
@endpush

