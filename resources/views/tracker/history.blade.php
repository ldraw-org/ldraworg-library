<x-layout.tracker>
    <x-slot:title>
        Parts Tracker History
    </x-slot>
    <div class="text-2xl font-bold">Parts Tracker History</div>
    <div>
        {!! $chart->render() !!}
    </div>
</x-layout.tracker>