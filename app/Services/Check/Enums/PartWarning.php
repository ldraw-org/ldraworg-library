<?php

namespace App\Services\Check\Enums;

use App\Enums\Traits\CanBeOption;
use App\Services\Check\Contracts\CheckItem;
use Filament\Support\Contracts\HasLabel;

enum PartWarning: string implements CheckItem, HasLabel
{
    use CanBeOption;

    case WarningMinifigCategory = 'warning.minifigcategory';
    case WarningNotCoplanar = 'warning.notcoplaner';
    case WarningStickerColor = 'warning.stickercolor';
    case WarningLicense = 'warning.license';
    case WarningDescriptionNumberSpaces = 'warning.descriptionnumbers';
    case WarningDecimalPrecision = 'warning.decimalprecision';
    case WarningPreviewInvalid = 'warning.previewinvalid';

    public function type(): CheckType
    {
        return CheckType::Warning;
    }

    public function isMultiLine(): bool
    {
        return match($this) {
            self::WarningNotCoplanar,
            self::WarningDecimalPrecision => true,
            default => false,
        };
    }

    public function multiLineHeader(): ?string
    {
        return match($this) {
            self::WarningNotCoplanar => 'Quad is not coplanar',
            self::WarningDecimalPrecision => 'Decimal precision exceeds library recommendation',
            default => null
        };
    }
}
