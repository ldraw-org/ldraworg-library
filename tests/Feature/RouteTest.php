<?php

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    User::factory()->create([
        'name' => 'TestUser',
        'realname' => 'Test User',
        'ca_confirm' => true,
    ]);
});

describe('non bound routes', function () {
    test('public route should return ok', function (string $routename) {
        $response = $this->get(route($routename));

        $response->assertOk();
    })
    ->with([
        'index',
        'model-viewer',
        'pbg',
        'icon-demo',
        'part-update.index',
        'parts.list',
        'parts.sticker-sheet.index',
        'parts.search.suffix',
        'tracker.main',
        'tracker.weekly',
        'tracker.history',
        'tracker.activity',
        'tracker.next-release',
        'omr.main',
        'omr.sets.index',
        'documentation.index',
        'categories-txt',
        'library-csv',
        'part.latest',
        'part.weekly-api',
    ]);

    test('auth routes', function (string $routename, array $permissions) {
        $response = $this->get(route($routename));
        $response->assertRedirect('/login');
        $user = User::query()->first();
        if ($permissions) {
            $user->syncPermissions([]);
            $user->save();
            $response = $this->actingAs($user)->get(route($routename));
            $response->assertForbidden();
        }
        $user->syncPermissions($permissions);
        $user->save();
        $response = $this->actingAs($user)->get(route($routename));
        $response->assertOk();
    })
    ->with([
        ['tracker.submit', [Permission::PartSubmitRegular]],
        ['tracker.torso-helper', [Permission::PartSubmitRegular]],
        ['tracker.release.create', [Permission::PartReleaseCreate]],
        ['dashboard.index', []],
        ['admin.index', [Permission::AdminDashboardView]],
        ['admin.users.index', [Permission::UserAdd]],
        ['admin.summaries.index', [Permission::ReviewSummaryManage]],
        ['admin.roles.index', [Permission::RoleManage]],
        ['admin.documents.index', [Permission::DocumentManage]],
        ['admin.document-categories.index', [Permission::DocumentCategoryManage]],
        ['admin.part-keywords.index', [Permission::PartKeywordsManage]],
        ['admin.settings.index', [Permission::SiteSettingsEdit]],
    ]);
});
