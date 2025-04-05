<?php

namespace App\Http\Controllers\Part;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Part\Part;
use Illuminate\Support\Carbon;

class WeeklyPartsController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->has('date')) {
            $date = new Carbon($request->get('date'));
        } else {
            $date = now();
        }
        $date->setTime(0, 0, 0);
        $date->setDaysFromStartOfWeek(0, \Carbon\CarbonInterface::SUNDAY);
        $parts = Part::doesntHave('official_part')->partsFolderOnly()->where('week', $date->format('Y-m-d'))->get();
        return \App\Http\Resources\PartsResource::collection($parts);
    }

}
