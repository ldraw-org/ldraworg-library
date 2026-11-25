<?php

namespace App\Services\Support;

/**
 * Lets a heavy queued job raise PHP's memory limit for itself. Horizon only recycles a worker once a job
 * leaves it over its memory threshold, so a job can start on a worker already holding most of a low limit.
 */
class MemoryLimit
{
    /**
     * Raise the memory limit to the given size, leaving a higher or unlimited one alone.
     *
     * @param string $limit a php.ini quantity such as '512M'
     */
    public function raiseTo(string $limit): void
    {
        $current = ini_parse_quantity(ini_get('memory_limit'));

        if ($current > 0 && $current < ini_parse_quantity($limit)) {
            ini_set('memory_limit', $limit);
        }
    }
}
