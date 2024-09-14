<?php

use App\Models\PartRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Public Routes
test('library main', function () {
    PartRelease::factory()->create();
    $response = $this->get('/');
    $response->assertOK();
});

test('part updates', function () {
    $response = $this->get('/updates');
    $response->assertOK();
});

test('latest updates', function () {
    $response = $this->get('/updates', ['latest']);
    $response->assertOK();
});

test('categories.txt', function () {
    $response = $this->get('/categories.txt');
    $response->assertOK();
});

test('library.csv', function () {
    $response = $this->get('/library.csv');
    $response->assertOK();
});
