<?php

namespace App\Http\Controllers;

use App\Models\ReviewSummary\ReviewSummary;

class ReviewSummaryController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ReviewSummary $summary)
    {
        return view('tracker.review-summary-show', compact('summary'));
    }
}
