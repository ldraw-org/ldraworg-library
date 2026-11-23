<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected $seed = true;

    /**
     * Refuses to set up a test against a database that is not a testing one, so a misconfigured environment
     * cannot reach the dev data. Guarded here rather than in beforeRefreshingDatabase(), which a test using
     * RefreshDatabase replaces with the trait's own.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits(): array
    {
        $database = DB::connection()->getDatabaseName();

        if (!str_contains($database, 'testing')) {
            throw new RuntimeException("Refusing to run tests against the non-testing database [{$database}]");
        }

        return parent::setUpTraits();
    }
}
