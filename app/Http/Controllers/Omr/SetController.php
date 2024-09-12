<?php

namespace App\Http\Controllers\Omr;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Omr\Set;
use Illuminate\View\View;

class SetController extends Controller
{
    public function index(Request $request): View
    {
        return view('omr.index');
    }

    public function show(Set $set): View
    {
        $set->load('models');
        return view('omr.show', compact('set'));
    }
}
