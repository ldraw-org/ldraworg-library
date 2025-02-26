<?php

namespace App\Http\Controllers;

use App\Enums\PartStatus;
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
                PartStatus::Certified->name => $h->history_data[PartStatus::Certified->value],
                PartStatus::AwaitingAdminReview->name => $h->history_data[PartStatus::AwaitingAdminReview->value],
                PartStatus::NeedsMoreVotes->name => $h->history_data[PartStatus::NeedsMoreVotes->value],
                PartStatus::UncertifiedSubfiles->name => $h->history_data[PartStatus::UncertifiedSubfiles->value] ?? 0,
                PartStatus::ErrorsFound->name => $h->history_data[PartStatus::ErrorsFound->value],
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
                    'label' => PartStatus::ErrorsFound->label(),
                    'data' => array_column($data, PartStatus::ErrorsFound->name),
                    'backgroundColor' => PartStatus::ErrorsFound->chartColor(),
                    'barThickness' => 1,
                ],
                [
                    'label' => PartStatus::UncertifiedSubfiles->label(),
                    'data' => array_column($data, PartStatus::UncertifiedSubfiles->name),
                    'backgroundColor' => PartStatus::UncertifiedSubfiles->chartColor(),
                    'barThickness' => 1,
                ],
                [
                    'label' => PartStatus::NeedsMoreVotes->label(),
                    'data' => array_column($data, PartStatus::NeedsMoreVotes->name),
                    'backgroundColor' => PartStatus::NeedsMoreVotes->chartColor(),
                    'barThickness' => 1,
                ],
                [
                    'label' => PartStatus::AwaitingAdminReview->label(),
                    'data' => array_column($data, PartStatus::AwaitingAdminReview->name),
                    'backgroundColor' => PartStatus::AwaitingAdminReview->chartColor(),
                    'barThickness' => 1,
                ],
                [
                    'label' => PartStatus::Certified->label(),
                    'data' => array_column($data, PartStatus::Certified->name),
                    'backgroundColor' => PartStatus::Certified->chartColor(),
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
