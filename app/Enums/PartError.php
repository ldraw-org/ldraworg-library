<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum PartError: string implements HasLabel
{
    use CanBeOption;

    case InvalidFileFormat = 'fileformat';
    case DuplicateFile = 'duplicate';

    case ReplaceNotSelected = 'replace';
    case FixNotSelected = 'fix.checked';

    case MissingHeaderMeta = 'missing';
    case AuthorInvalid = 'authorinvalid';

    case CircularReference = 'circularreference';
    case BfcNotCcw = 'bfc';
    case PreviewInvalid = 'previewinvalid';

    case LineInvalid = 'line.invalid';
    case InvalidLineType0 = 'line.invalidmeta';
    case InvalidLineColor = 'line.invalidcolor';
    case InvalidColoredLines = 'line.invalidcoloredlines';
    case InvalidColor16 = 'line.invalidcolor16';
    case InvalidColor24 = 'line.invalidcolor24';
    case InvalidLineNumbers = 'line.invalidnumbers';
    case RotationMatrixIsSingular = 'line.singular';
    case IdenticalPoints = 'line.identicalpoints';
    case PointsColinear = 'line.colinear';
    case QuadNotConvex = 'line.notconvex';
    case QuadNotCoplanar = 'line.notcoplaner';

    case PartNameInvalid = 'name.invalidchars';
    case NameAndFilenameNotEqual = 'name.mismatch';
    case UnknownPartNumberName = 'name.xparts';
    case FlexSectionIncorrectSuffix = 'name.flex';

    case InvalidDescription = 'description.invalidchars';
    case PatternNotInDescription = 'description.patternword';
    case NoTildeForSubpart = 'description.subpartdesc';
    case NoEqualsForAlias = 'description.aliasdesc';
    case NoPipeForThirdParty = 'description.thirdpartydesc';
    case NoTildeForMovedObsolete = 'description.movedorobsolete';

    case ImproperObsolete = 'obsoleteimproper';

    case NameTypeMismatch = 'type.path';
    case NewPartIsPhysicalColor = 'type.phycolor';
    case AliasNotInParts = 'type.alias';
    case FlexSectionNotPart = 'type.flex';

    case LicenseNotLibraryApproved = 'license.approved';

    case CategoryInvalid = 'category.invalid';

    case NoSetKeywordForPattern = 'keywords.patternset';

    case DecimalPrecision = 'numbers.decimalprecision';
    case TrailingZeros = 'numbers.trailingzeros';
    case LeadingZeros = 'numbers.leadingzeros';

    case HistoryInvalid = 'history.invalid';
    case HistoryAuthorNotRegistered = 'history.authorregistered';

    case TrackerNoCertifiedParents = 'tracker_hold.nocertparents';
    case TrackerHasUncertifiedSubfiles = 'tracker_hold.uncertsubs';
    case TrackerHasMissingSubfiles = 'tracker_hold.missing';
    case TrackerAdminHold = 'tracker_hold.adminhold';

    case WarningMinifigCategory = 'warning.minifigcategory';
    case WarningNotCoplanar = 'warning.notcoplaner';
    case WarningStickerColor = 'warning.stickercolor';
    case WarningLicense = 'warning.license';
    case WarningDescriptionNumberSpaces = 'warning.descriptionnumbers';
    case WarningDecimalPrecision = 'warning.decimalprecision';

    public function isMultiLine(): bool
    {
        return match($this) {
            self::LineInvalid,
            self::InvalidLineType0,
            self::InvalidLineColor,
            self::InvalidColoredLines,
            self::InvalidColor16,
            self::InvalidColor24,
            self::InvalidLineNumbers,
            self::RotationMatrixIsSingular,
            self::IdenticalPoints,
            self::PointsColinear,
            self::QuadNotConvex,
            self::QuadNotCoplanar,
            self::DecimalPrecision,
            self::TrailingZeros,
            self::LeadingZeros,
            self::WarningNotCoplanar,
            self::WarningDecimalPrecision => true,
            default => false,
        };
    }

    public function multiLineHeader(): ?string
    {
        if (!$this->isMultiLine()) {
            return null;
        }

        return match($this) {
            self::LineInvalid => 'Invalid Line',
            self::InvalidLineType0 => 'Invalid META command or comment without //',
            self::InvalidLineColor => 'Color code not in LDConfig.ldr',
            self::InvalidColoredLines => 'Linetypes 2 and 5 should only be color 24',
            self::InvalidColor16 => 'Color code 16 not allowed for linetypes 2, 5',
            self::InvalidColor24 => 'Color code 24 not allowed for linetypes 1, 3, 4',
            self::InvalidLineNumbers => 'Invalid number format',
            self::RotationMatrixIsSingular => 'Singular rotation matrix',
            self::IdenticalPoints => 'Identical points',
            self::PointsColinear => 'Points are colinear',
            self::QuadNotConvex => 'Quad is concave or bowtie',
            self::QuadNotCoplanar,
            self::WarningNotCoplanar => 'Quad is not coplanar',
            self::DecimalPrecision,
            self::TrailingZeros,
            self::LeadingZeros => __('partcheck.' . $this->value),
            self::WarningDecimalPrecision => 'Decimal precision exceeds library recommendation'
        };
    }
}
