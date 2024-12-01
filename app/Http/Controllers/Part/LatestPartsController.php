<?php

namespace App\Http\Controllers\Part;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Part\PartEvent;

class LatestPartsController extends Controller
{
    public function __invoke(Request $request)
    {
        $parts = PartEvent::with(['part'])
            ->where('initial_submit', true)
            ->whereHas('part', fn ($query) =>
                $query->unofficial()->partsFolderOnly()
            )
            ->latest()
            ->take(8)
            ->get()
            ->pluck('part');
        return \App\Http\Resources\PartsResource::collection($parts);
    }

}
