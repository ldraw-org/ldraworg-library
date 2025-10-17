<?php

namespace App\Http\Controllers\Part;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartReleaseResource;
use App\Models\Part\PartRelease;
use Illuminate\Http\JsonResponse;

class LatestReleaseController extends Controller
{
    public function __invoke(): PartReleaseResource
    {
        return new PartReleaseResource(PartRelease::current());
    }

}
