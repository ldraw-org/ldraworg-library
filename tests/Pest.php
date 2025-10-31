<?php

use App\Services\Parser\ParsedPartCollection;
use App\Services\Check\PartChecker;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)->in('Feature');

expect()->extend('toHaveCheckResult', function (bool $expected, string $check, ?string $filename = null) {
    $file = new ParsedPartCollection($this->value);
    $check_namespace = '\\App\\Services\\Check\\PartChecks\\';
    $check = $check_namespace . $check;
    $check = new $check();
    $result = PartChecker::singleCheck($file, $check, $filename);
    expect($result->isEmpty())->toBe($expected);
});
