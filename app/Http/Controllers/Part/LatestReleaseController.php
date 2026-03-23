<?php

namespace App\Http\Controllers\Part;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartReleaseResource;
use App\Models\Part\PartRelease;
use App\Services\PartReleaseService;
use Illuminate\Support\Facades\Cache;

class LatestReleaseController extends Controller
{
    public function __invoke(PartReleaseService $releaseService): PartReleaseResource
    {
        return new PartReleaseResource($releaseService->currentRelease());
    }

}
