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
    <div class="relative h-[32rem]">
        <canvas id="ptHistory" role="img" aria-label="Stacked area chart of daily unofficial part counts by status"></canvas>
    </div>
    @push('scripts')
        <script type="module">
            const dates = @js($dates);
            const series = @js($datasets);
            const chart = new Chart(document.getElementById('ptHistory'), {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: series.map((dataset, index) => ({
                        ...dataset,
                        fill: index === 0 ? 'origin' : '-1',
                        borderColor: '#ffffff',
                        borderWidth: 1.5,
                        pointRadius: 0,
                        pointHoverRadius: 0,
                    })),
                },
                options: {
                    maintainAspectRatio: false,
                    animation: false,
                    normalized: true,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: { ticks: { maxTicksLimit: 10, maxRotation: 0 }, grid: { display: false } },
                        y: { stacked: true, beginAtZero: true, grid: { color: '#e5e7eb' } },
                    },
                    plugins: {
                        legend: { reverse: true },
                        tooltip: {
                            itemSort: (a, b) => b.datasetIndex - a.datasetIndex,
                            callbacks: {
                                footer: (items) => 'Total: ' + items.reduce((sum, item) => sum + item.parsed.y, 0).toLocaleString(),
                            },
                        },
                    },
                },
            });

            const buttons = document.querySelectorAll('#ptHistoryRange button');
            buttons.forEach((button) => button.addEventListener('click', () => {
                let start = 0;
                if (button.dataset.years) {
                    const cutoff = new Date();
                    cutoff.setFullYear(cutoff.getFullYear() - Number(button.dataset.years));
                    const cutoffDate = cutoff.toISOString().slice(0, 10);
                    start = Math.max(0, dates.findIndex((date) => date >= cutoffDate));
                }
                chart.data.labels = dates.slice(start);
                chart.data.datasets.forEach((dataset, index) => dataset.data = series[index].data.slice(start));
                chart.update();
                buttons.forEach((other) => other.setAttribute('aria-pressed', other === button ? 'true' : 'false'));
            }));
        </script>
    @endpush
</x-layout.tracker>
