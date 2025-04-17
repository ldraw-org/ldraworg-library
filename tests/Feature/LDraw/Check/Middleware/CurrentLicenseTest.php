<?php

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('CA affirm middleware', function () {
    $user = User::factory()->create();
    $user->syncPermissions([Permission::PartSubmitRegular]);

    $route = route('tracker.submit');

    $response = $this->actingAs($user)->get($route);
    $response->assertRedirectToRoute('tracker.confirmCA.show');

    $user->ca_confirm = true;
    $user->save();

    $response = $this->actingAs($user)->get($route);
    $response->assertOk();
});
