<?php

namespace App\Services\Check\Enums;

use App\Enums\Traits\CanBeOption;
use App\Services\Check\Contracts\CheckItem;
use App\Services\Check\Enums\Traits\HasMessage;
use Filament\Support\Contracts\HasLabel;

enum PartWarning: string implements CheckItem, HasLabel
{
    use CanBeOption;
    use HasMessage;

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

    public function description(): string
    {
        return match ($this) {
            self::WarningNotCoplanar => 'Line :line, quad is not coplanar (angle :value)',
            self::WarningMinifigCategory => 'Ensure correct Minifig category',
            self::WarningStickerColor => 'Ensure stickers that are not color 16 are applied to defined transparent surfaces',
            self::WarningLicense => 'Parts without CC BY 4.0 license may not be released',
            self::WarningDescriptionNumberSpaces => 'Number in description may need a leading space',
            self::WarningDecimalPrecision => ':value decimal places exceeds the recommendation for :type in the library specification',
            self::WarningPreviewInvalid => 'PREVIEW line uses non-standard values, reverting to default',
        };
    }
}
