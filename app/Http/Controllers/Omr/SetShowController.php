<?php

namespace App\Http\Controllers\Omr;

use App\Http\Controllers\Controller;
use App\Models\Omr\Set;
use Illuminate\View\View;

class SetShowController extends Controller
{
    public function __invoke(Set $set): View
    {
        $set->load('models');
        return view('omr.show', compact('set'));
    }
}
