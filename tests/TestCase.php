<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    protected $seed = true;
    
    protected function tearDown(): void
    {
        //DB::connection()->setPdo(null);
        parent::tearDown();
    }
}
