<?php

namespace App\Http\Controllers\Part;

use App\Http\Resources\PartsResource;
use App\Http\Controllers\Controller;
use App\Models\Part\PartEvent;
use Illuminate\Http\Resources\Json\ResourceCollection;
  
class LatestPartsController extends Controller
{
    public function __invoke(): ResourceCollection
    {
        $parts = PartEvent::with(['part'])
            ->where('initial_submit', true)
            ->whereHas(
                'part',
                fn ($query) =>
                $query->unofficial()->partsFolderOnly()
            )
            ->latest()
            ->take(8)
            ->get()
            ->pluck('part');
        return PartsResource::collection($parts);
    }

}
