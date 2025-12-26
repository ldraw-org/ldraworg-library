<?php

namespace App\Services\Menu\Enums;

use App\Services\Menu\Contracts\Navigable;

enum LibraryNavItem implements Navigable
{
    case LdrawOrg;
    case Library;
    case PartTracker;
    case LatestUpdate;
    case Updates;
    case Omr;
    case PbgGenerator;
    case ModelViewer;
    case UserList;
    case Documentation;

    public function label(): string
    {
        return match ($this) {
            self::LdrawOrg => 'LDraw.org Main',
            self::Library => 'Library',
            self::PartTracker => 'Parts Tracker',
            self::LatestUpdate => 'Latest Update',
            self::Updates => 'Update Archive',
            self::Omr => 'OMR',
            self::PbgGenerator => 'PBG Generator',
            self::ModelViewer => 'LDraw Model Viewer',
            self::UserList => 'User List',
            self::Documentation => 'Documentation',
        };
    }

    public function route(): string
    {
        return match ($this) {
            self::LdrawOrg => 'https://www.ldraw.org',
            self::Library => route('index'),
            self::PartTracker => route('tracker.main'),
            self::LatestUpdate => route('part-update.index', ['latest']),
            self::Updates => route('part-update.index'),
            self::Omr => route('omr.main'),
            self::PbgGenerator => route('pbg'),
            self::ModelViewer => route('model-viewer'),
            self::UserList => route('users.index'),
            self::Documentation => route('documentation.index'),
        };
    }

    public function visible(): bool
    {
        return true;
    }
}