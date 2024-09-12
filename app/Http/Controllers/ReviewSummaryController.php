<?php

namespace App\Http\Controllers;

use App\Models\ReviewSummary\ReviewSummary;
use Illuminate\View\View;

class ReviewSummaryController extends Controller
{
    public function __invoke(ReviewSummary $summary): View
    {
        return view('tracker.review-summary-show', compact('summary'));
    }
}
