<?php

namespace App\Http\Controllers;

use App\Models\TrackerHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackerHistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $history = TrackerHistory::latest()->get();
        $data = [];
        foreach ($history as $h) {
            $data[] = [
                'certified' => $h->history_data[1],
                'needsreview' => $h->history_data[2],
                'needsvotes' => $h->history_data[3],
                'subparts' => $h->history_data[4] ?? 0,
                'held' => $h->history_data[5],
                'date' => date_format($h->created_at, 'Y-m-d'),
            ];
        }
        $chart = app()->chartjs
            ->name('ptHistory')
            ->type('bar')
            ->labels(array_column($data, 'date'))
            ->size(['width' => '100%', 'height' => min(count($data), 4875)])
            ->datasets([
                [
                    'label' => 'Held',
                    'data' => array_column($data, 'held'),
                    'backgroundColor' => 'rgba(255, 0, 0, 1)',
                    'barThickness' => 1,
                ],
                [
                    'label' => 'Uncertified Subfiles',
                    'data' => array_column($data, 'subparts'),
                    'backgroundColor' => 'rgba(255, 255, 0, 1)',
                    'barThickness' => 1,
                ],
                [
                    'label' => 'Needs Votes',
                    'data' => array_column($data, 'needsvotes'),
                    'backgroundColor' => 'rgba(204, 204, 204, 1)',
                    'barThickness' => 1,
                ],
                [
                    'label' => 'Needs Admin Review',
                    'data' => array_column($data, 'needsreview'),
                    'backgroundColor' => 'rgba(0, 0, 255, 1)',
                    'barThickness' => 1,
                ],
                [
                    'label' => 'Certified',
                    'data' => array_column($data, 'certified'),
                    'backgroundColor' => 'rgba(0, 255, 0, 1)',
                    'barThickness' => 1,
                ]
            ])
            ->optionsRaw("{
                indexAxis: 'y',
                maintainAspectRatio: false,
                animation: false,
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                    },
                }
            }");
        return view('tracker.history', compact('chart'));
    }
}
