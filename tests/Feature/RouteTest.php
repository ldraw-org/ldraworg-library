<?php

use App\Enums\Permission;
use App\Models\Document\Document;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    User::factory()->create([
        'name' => 'TestUser',
        'realname' => 'Test User',
        'ca_confirm' => true,
    ]);
    Document::factory()->create();
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
        'users.index'
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
        ['admin.ldconfig.index', [Permission::LdconfigEdit]],
        ['omr.add', [Permission::OmrModelApprove]],
    ]);
});

describe('part bound routes', function () {
    test('part routes', function () {
        $part = Part::factory()->hasBody(1)->create();
        $release = PartRelease::factory()->create();

        $response = $this->get("/parts/{$part->id}");
        $response->assertOk();

        $response = $this->get("/parts/unofficial/{$part->filename}");
        $response->assertOk();

        $part->release()->associate($release);
        $part->save();

        $response = $this->get("/parts/{$part->filename}");
        $response->assertOk();
    });
});

describe('document routes', function () {

    test('published, unrestricted document', function () {
        $user = User::query()->first();
        $document = Document::query()->first();
        $document->published = true;
        $document->save();

        $response = $this->actingAs($user)->get(route('documentation.show', [$document->category, $document]));
        $response->assertOk();
    });

    test('published, restricted document', function () {
        $document = Document::query()->first();
        $document->restricted = true;
        $document->published = true;
        $document->save();

        $user = User::query()->first();

        $user->syncPermissions([]);
        $user->save();
        $response = $this->actingAs($user)->get(route('documentation.show', [$document->category, $document]));
        $response->assertForbidden();

        $user->syncPermissions(Permission::DocumentViewRestricted);
        $user->save();
        $response = $this->actingAs($user)->get(route('documentation.show', [$document->category, $document]));
        $response->assertOk();
    });

    test('unpublished document', function () {
        $document = Document::query()->first();
        $document->published = false;
        $document->save();

        $user = User::query()->first();

        $user->syncPermissions([]);
        $user->save();
        $response = $this->actingAs($user)->get(route('documentation.show', [$document->category, $document]));
        $response->assertForbidden();

        $user->syncPermissions(Permission::DocumentViewUnpublished);
        $user->save();
        $response = $this->actingAs($user)->get(route('documentation.show', [$document->category, $document]));
        $response->assertOk();
    });

});
