<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected $seed = true;

    /**
     * Refuses to wipe a database that is not a testing one, so a misconfigured environment cannot reach the dev data.
     */
    protected function beforeRefreshingDatabase(): void
    {
        $database = DB::connection()->getDatabaseName();

        if (!str_contains($database, 'testing')) {
            throw new RuntimeException("Refusing to refresh the non-testing database [{$database}]");
        }
    }
}
