<?php

namespace App\Services\Cache;

use App\Services\LDConfig\AvatarLookup;
use App\Services\LDConfig\ColourLookup;
use App\Services\LibraryStatisticsService;
use App\Services\PartReleaseService;
use App\Services\User\UserList;
use Illuminate\Support\Facades\Cache;

enum CacheKey: string
{
    case PartReleaseCurrent = 'part_release_current';
    case OfficialPartCount = 'official_part_count';
    case LdrawColourCodes = 'ldraw_colour_codes';
    case LdrawColourOptions = 'ldraw_colour_options';
    case LdrawColourCodesToRebrickable = 'ldraw_colour_codes_to_rebrickable';
    case AvatarParts = 'avatar_parts';
    case UserOptions = 'user_options';

    public function reset(): void
    {
        Cache::forget($this);
    }

    public function warm(): void
    {
        match ($this) {
            self::PartReleaseCurrent =>
            app(PartReleaseService::class)->currentRelease(),

            self::OfficialPartCount =>
            app(LibraryStatisticsService::class)->officialPartCount(),

            self::LdrawColourCodes =>
            app(ColourLookup::class)->codes(),

            self::LdrawColourOptions =>
            app(ColourLookup::class)->options(),

            self::LdrawColourCodesToRebrickable =>
            app(ColourLookup::class)->codesToRebrickable(),

            self::AvatarParts =>
            app(AvatarLookup::class)->parts(),

            self::UserOptions =>
            app(UserList::class)->userOptions(),
        };
    }

}
