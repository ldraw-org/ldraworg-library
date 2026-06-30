<?php

namespace App\Services\Support;

use App\Services\Support\Enums\ReleaseFormat;
use App\Services\Support\Enums\ReleaseType;

readonly class ReleaseEntry
{
    public function __construct(
        public ReleaseType   $type,
        public ReleaseFormat $format,
        public string        $name,
        public string        $date,
        public string        $file,
    ) {}
}
