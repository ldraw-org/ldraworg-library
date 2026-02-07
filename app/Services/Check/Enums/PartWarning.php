<?php

namespace App\Services\Check\Enums;

enum PartError: string
{
    case WarningMinifigCategory = 'warning.minifigcategory';
    case WarningNotCoplanar = 'warning.notcoplaner';
    case WarningStickerColor = 'warning.stickercolor';
    case WarningLicense = 'warning.license';
    case WarningDescriptionNumberSpaces = 'warning.descriptionnumbers';
    case WarningDecimalPrecision = 'warning.decimalprecision';

    public function type(): CheckType
    {
        return CheckType::Warning;
    }

    public function checkClass(): string
    {
        return match($this) {
            self::WarningMinifigCategory => App\Services\Check\PartChecks\MinifigCategoryWarning::class,
            self::WarningNotCoplanar => App\Services\Check\PartChecks\ValidLines::class,
            self::WarningStickerColor => App\Services\Check\PartChecks\StickerColorWarning::class,
            self::WarningLicense => App\Services\Check\PartChecks\LibraryLicenseWarning::class,
            self::WarningDescriptionNumberSpaces => App\Services\Check\PartChecks\DescriptionNumberWarning::class,
            self::WarningDecimalPrecision => App\Services\Check\PartChecks\ValidLines::class,
        };
    }
}
