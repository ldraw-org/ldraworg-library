<x-layout.tracker>
    <x-slot:title>
        Parts Tracker History
    </x-slot>
    <x-page-title>Parts Tracker History</x-page-title>
    <div class="flex flex-wrap items-center justify-between gap-2 py-2">
        <x-part.unofficial-part-count />
        <div id="ptHistoryRange" class="flex gap-1" role="group" aria-label="Date range">
            @foreach (['All' => null, '10 years' => 10, '5 years' => 5, '1 year' => 1] as $label => $years)
                <button
                    type="button"
                    data-years="{{ $years }}"
                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                    class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-100 aria-pressed:bg-gray-800 aria-pressed:text-white"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>
    <div id="ptHistoryContainer" class="relative">
        <canvas id="ptHistory" role="img" aria-label="Stacked bar chart of daily unofficial part counts by status"></canvas>
    </div>
    @push('scripts')
        <script type="module">
            const dates = @js($dates).reverse();
            const series = @js($datasets).map((dataset) => ({ ...dataset, data: dataset.data.reverse() }));
            const container = document.getElementById('ptHistoryContainer');
            const setHeight = (days) => container.style.height = Math.min(days, 4875) + 'px';
            setHeight(dates.length);
            const chart = new Chart(document.getElementById('ptHistory'), {
                type: 'bar',
                data: {
                    labels: dates,
                    datasets: series.map((dataset) => ({ ...dataset, data: [...dataset.data], barThickness: 1 })),
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    animation: false,
                    normalized: true,
                    interaction: { mode: 'index', axis: 'y', intersect: false },
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true },
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                footer: (items) => 'Total: ' + items.reduce((sum, item) => sum + item.parsed.x, 0).toLocaleString(),
                            },
                        },
                    },
                },
            });

            const buttons = document.querySelectorAll('#ptHistoryRange button');
            buttons.forEach((button) => button.addEventListener('click', () => {
                let end = dates.length;
                if (button.dataset.years) {
                    const cutoff = new Date();
                    cutoff.setFullYear(cutoff.getFullYear() - Number(button.dataset.years));
                    const cutoffDate = cutoff.toISOString().slice(0, 10);
                    const firstOlder = dates.findIndex((date) => date < cutoffDate);
                    end = firstOlder === -1 ? dates.length : firstOlder;
                }
                chart.data.labels = dates.slice(0, end);
                chart.data.datasets.forEach((dataset, index) => dataset.data = series[index].data.slice(0, end));
                setHeight(end);
                chart.update();
                buttons.forEach((other) => other.setAttribute('aria-pressed', other === button ? 'true' : 'false'));
            }));
        </script>
    @endpush
</x-layout.tracker>
