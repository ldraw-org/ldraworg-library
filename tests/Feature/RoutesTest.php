<?php

use App\Models\User;

use function Spatie\RouteTesting\routeTesting;

routeTesting('all parts routes')
    ->setUp(function () {
        $user = User::factory()->create();

        $this->actingAs($user);
    })
    ->include('parts*', 'tracker*')
    ->ignoreRoutesWithMissingBindings()
    ->assertSuccessful();
