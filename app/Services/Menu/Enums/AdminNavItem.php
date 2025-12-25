<?php

namespace App\Services\Menu\Enums;

use App\Services\Menu\Contracts\Navigable;
use Illuminate\Support\Facades\Auth;

enum AdminNavItem implements Navigable
{
    case SiteSettings;
    case LdConfigEdit;
    case KeywordManage;
    case UsersManage;
    case RolesManage;
    case DocumentationManage;
    case DocumentationCategoryManage;
    case ReviewSummariesManage;

    public function label(): string
    {
        return match($this) {
            self::SiteSettings => 'General Site Settings',
            self::LdConfigEdit => 'Edit LDConfig',
            self::KeywordManage => 'View/Edit Part Keywords',
            self::UsersManage => 'Add/Edit Users',
            self::RolesManage => 'Add/Edit Roles',
            self::DocumentationManage => 'Add/Edit Documentation',
            self::DocumentationCategoryManage => 'Add/Edit Documentation Categories',
            self::ReviewSummariesManage => 'Add/Edit Review Summaries',
        };
    }

    public function route(): string
    {
        return match($this) {
            self::SiteSettings => route('admin.settings.index'),
            self::LdConfigEdit => route('admin.ldconfig.index'),
            self::KeywordManage => route('admin.part-keywords.index'),
            self::UsersManage => route('admin.users.index'),
            self::RolesManage => route('admin.roles.index'),
            self::DocumentationManage => route('admin.documents.index'),
            self::DocumentationCategoryManage => route('admin.document-categories.index'),
            self::ReviewSummariesManage => route('admin.summaries.index'),
        };
    }

    public function visible(): bool
    {
        return match($this) {
            self::SiteSettings => Auth::user()?->can(\App\Enums\Permission::SiteSettingsEdit) ?? false,
            self::LdConfigEdit => Auth::user()?->can(\App\Enums\Permission::LdconfigEdit) ?? false,
            self::KeywordManage => Auth::user()?->can('manage', \App\Models\Part\PartKeyword::class) ?? false,
            self::UsersManage => Auth::user()?->can('add', \App\Models\User::class) ?? false,
            self::RolesManage => Auth::user()?->can('viewAny', \Spatie\Permission\Models\Role::class) ?? false,
            self::DocumentationManage => Auth::user()?->can('manage', \App\Models\Document\Document::class) ?? false,
            self::DocumentationCategoryManage => Auth::user()?->can('manage', \App\Models\Document\Document::class) ?? false,
            self::ReviewSummariesManage => Auth::user()?->can('manage', \App\Models\ReviewSummary::class) ?? false,
        };
    }

}