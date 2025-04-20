<?php

use App\Enums\Permission;
use App\Models\Mybb\MybbUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test ('mybb login', function () {
    $u = MybbUser::query()->first();
    $user = User::factory()->create([
        'forum_user_id' => $u->uid
    ]);
    $user->save();

    $route = route('index');

    $this->get($route);
    $this->assertGuest();

    $this->withUnencryptedCookie('mybbuser', Str::random(30))->get($route);
    $this->assertGuest();

    $this->withUnencryptedCookie('mybbuser', "{$u->uid}_XXXXXXXXXXXX")->get($route);
    $this->assertGuest();

    $this->withUnencryptedCookie('mybbuser', "12345_{$u->loginkey}")->get($route);
    $this->assertGuest();

    $this->withUnencryptedCookie('mybbuser', "{$u->uid}_{$u->loginkey}")->get($route);
    $this->assertAuthenticatedAs($user);
});