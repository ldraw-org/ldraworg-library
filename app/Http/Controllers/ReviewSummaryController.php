<?php

namespace App\Http\Controllers;

use App\Models\ReviewSummary\ReviewSummary;
use Illuminate\View\View;

class ReviewSummaryController extends Controller
{
    public function __invoke(ReviewSummary $summary): View
    {
        $list = explode("\n", $summary->list);
        $parts = $summary->parts();
        return view('tracker.review-summary-show', compact('summary', 'list', 'parts'));
    }
}
