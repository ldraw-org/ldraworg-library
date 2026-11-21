<x-slot:title>Recent Activity</x-slot>
<div>
    <div id="activityTable"></div>
    {{ $this->table }}
</div>

@script
<script>
    $wire.on('page-change', () => {
        document.getElementById("activityTable").scrollIntoView();
    });
</script>
@endscript
