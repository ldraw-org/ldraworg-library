<?php

namespace App\Http\Controllers\Part;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Part\PartEvent;

class LatestPartsController extends Controller
{
    public function __invoke(Request $request)
    {
        $events = PartEvent::with(['part'])->where('initial_submit', true)->whereHas('part', function ($q) {
            $q->whereRelation('type', 'folder', 'parts/')->whereNull('part_release_id');
        })->latest()->take(8)->get();
        return \App\Http\Resources\LatestPartsResource::collection($events);
    }

}
