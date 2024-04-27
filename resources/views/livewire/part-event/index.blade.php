<x-slot:title>Recent Activity</x-slot>
<x-slot:breadcrumbs>
    <x-breadcrumb-item class="active" item="Activity" />
</x-slot>
<div>
    <div class="flex flex-col space-y-4">
        <div class="grid grid-cols-2 justify-stretch items-center">
            <div class="justify-self-start">
                <p class="text-2xl font-bold">Parts Tracker Activity Log</p>
            </div>
            <div class="justify-self-end">
                <p class="text-right">Server Time: {{date('Y-m-d H:i:s')}}</p>
                <x-part.unofficial-part-count />
            </div>
        </div>
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