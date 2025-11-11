<?php

namespace App\Http\Controllers;

use App\Enums\PartStatus;
use App\Models\TrackerHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class TrackerHistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $data = TrackerHistory::latest()
            ->get()
            ->map(function (TrackerHistory $h) {
                return [
                    PartStatus::Certified->name => Arr::get($h->history_data, PartStatus::Certified->value, 0),
                    PartStatus::AwaitingAdminReview->name => Arr::get($h->history_data, PartStatus::AwaitingAdminReview->value, 0),
                    PartStatus::Needs1MoreVote->name => Arr::get($h->history_data, PartStatus::Needs2MoreVotes->value, 0) + 
                        Arr::get($h->history_data, PartStatus::Needs1MoreVote->value, 0),
                    PartStatus::UncertifiedSubfiles->name => 
                        Arr::get($h->history_data, PartStatus::UncertifiedSubfiles->value, 0) == Arr::get($h->history_data, PartStatus::ErrorsFound->value)
                        ? 0 
                        : Arr::get($h->history_data, PartStatus::UncertifiedSubfiles->value, 0),
                    PartStatus::ErrorsFound->name => Arr::get($h->history_data, PartStatus::ErrorsFound->value, 0),
                    'date' => date_format($h->created_at, 'Y-m-d'),
                ];
            })
            ->all();
        //dd($data[100]);
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
                    'label' => "Needs More Votes",
                    'data' => array_column($data, PartStatus::Needs1MoreVote->name),
                    'backgroundColor' => PartStatus::Needs1MoreVote->chartColor(),
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
