<x-slot:title>Recent Activity</x-slot>
<div>
    <div class="flex flex-col space-y-2">
        <div class="grid grid-cols-2 justify-stretch items-center bg-white rounded-lg p-2">
            <x-page-title class="justify-self-start">Parts Tracker Activity Log</x-page-title>
            <div class="justify-self-end">
                <x-part.unofficial-part-count />
            </div>
        </div>
        @can('create', App\Models\Part\PartRelease::class)
            <livewire:release.progress />
        @endcan
        <div id="activityTable"></div>
        {{ $this->table }}
    </div>
</div>

@script
<script>
    $wire.on('page-change', () => {
        document.getElementById("activityTable").scrollIntoView();
    });
</script>
@endscript
