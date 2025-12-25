<?php

namespace App\Services\Menu;

use App\Services\Menu\Enums\LibraryNavItem;
use App\Services\Menu\Enums\TrackerNavItem;
use App\Services\Menu\Enums\OtherNavItem;
use App\Models\ReviewSummary;
use App\Services\Menu\Enums\AdminNavItem;

class MenuRegistry
{
    public static function omr(): array
    {
        return [
            ['label' => 'Library', 'children' => self::library()],
            OtherNavItem::SetList,
            LibraryNavItem::Documentation,
        ];
    }

    public static function library(): array
    {
        return [
            LibraryNavItem::LdrawOrg,
            LibraryNavItem::Library,
            TrackerNavItem::PartList,
            LibraryNavItem::PartTracker,
            LibraryNavItem::Omr,
            LibraryNavItem::LatestUpdate,
            LibraryNavItem::Updates,
            ['label' => 'Tools', 'children' => [
                LibraryNavItem::PbgGenerator,
                LibraryNavItem::ModelViewer,
                LibraryNavItem::UserList,
            ]],
            LibraryNavItem::Documentation,
        ];
    }

    public static function admin(): array
    {
        return [
            ['label' => 'Library', 'children' => self::library()],
            ['label' => 'Parts Tracker', 'children' => self::tracker()],
            ['label' => 'Library General', 'children' =>[
                AdminNavItem::SiteSettings,
                AdminNavItem::LdConfigEdit,
                AdminNavItem::KeywordManage,
            ]],
            ['label' => 'Users', 'children' =>[
                AdminNavItem::UsersManage,
                AdminNavItem::RolesManage,
            ]],
            ['label' => 'Documentation', 'children' =>[
                AdminNavItem::DocumentationManage,
                AdminNavItem::DocumentationCategoryManage,
            ]],
            AdminNavItem::ReviewSummariesManage,
        ];
    }

    public static function tracker(): array
    {
        return [
            ['label' => 'Library', 'children' => self::library()],
            TrackerNavItem::Submit,
            TrackerNavItem::PartList,
            TrackerNavItem::PartActivity,
            TrackerNavItem::WeeklyParts,
            LibraryNavItem::Documentation,
            ['label' => 'Other', 'children' => [
                ['label' => 'Downloads', 'children' =>[
                    OtherNavItem::UnofficialPartZip,
                    OtherNavItem::DailySubmitsZip
                ]],
                ['label' => 'Review Summaries', 'children' => self::getSummaries()],
                TrackerNavItem::CategoryList,
                TrackerNavItem::PartSummary,
                TrackerNavItem::StickerSheets,
                TrackerNavItem::TorsoHelper,
                TrackerNavItem::NextUpdate,
                TrackerNavItem::TrackerHistory
            ]],
        ];
    }
    
    protected static function getSummaries(): array
    {
        return ReviewSummary::ordered()->get()->map(fn($s) => [
            'label' => $s->header,
            'link' => route('tracker.summary.view', $s)
        ])->toArray();
    }
}
