<?php

namespace App\Http\Controllers\Part;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartReleaseResource;
use App\Services\PartReleaseService;

class LatestReleaseController extends Controller
{
    public function __invoke(PartReleaseService $releaseService): PartReleaseResource
    {
        return new PartReleaseResource($releaseService->currentRelease());
    }

}
