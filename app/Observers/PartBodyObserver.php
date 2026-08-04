<?php

namespace App\Observers;

use App\Jobs\CheckPart;
use App\Jobs\GeneratePartImage;
use App\Jobs\UpdateImage;
use App\Models\Part\Part;
use App\Models\Part\PartBody;
use App\Services\Part\ImageGenerator;
use App\Services\Part\SyncSubparts;
use App\Services\Part\SyncUnknownPartNumber;
use Illuminate\Support\Facades\Log;

class PartBodyObserver
{
    public function __construct(
    ) {}
    public function saved(PartBody $body): void
    {
    }
}
