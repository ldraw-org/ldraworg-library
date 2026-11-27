<?php

namespace App\Http\Controllers;

use App\Enums\PartStatus;
use App\Services\LibraryStatisticsService;
use Illuminate\View\View;

class TrackerHistoryController extends Controller
{
    public function __invoke(LibraryStatisticsService $statistics): View
    {
        $history = $statistics->trackerHistory();

        $datasets = collect($history['counts'])
            ->map(fn (array $counts, string $series): array => [
                'label' => $series === PartStatus::Needs1MoreVote->name
                    ? 'Needs More Votes'
                    : PartStatus::{$series}->label(),
                'data' => $counts,
                'backgroundColor' => PartStatus::{$series}->chartColor(),
            ])
            ->values()
            ->all();

        return view('tracker.history', [
            'dates' => $history['dates'],
            'datasets' => $datasets,
        ]);
    }
}
