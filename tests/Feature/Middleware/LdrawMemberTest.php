<?php

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ldraw member middleware', function () {
    $route = route('poll.index');

    $user = User::factory()->create();

    $response = $this->get($route);
    $response->assertRedirectToRoute('joinldraw');

    $response = $this->actingAs($user)->get($route);
    $response->assertRedirectToRoute('joinldraw');

    $user->syncPermissions([Permission::LdrawMemberAccess]);
    $response = $this->actingAs($user)->get($route);
    $response->assertForbidden();
});
