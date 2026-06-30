<?php

namespace App\Services\Check\Enums;

use App\Enums\Traits\CanBeOption;
use App\Services\Check\Contracts\CheckItem;
use App\Services\Check\Enums\Traits\HasMessage;
use Filament\Support\Contracts\HasLabel;

enum PartError: string implements CheckItem, HasLabel
{
    use CanBeOption;
    use HasMessage;

    case InvalidFileFormat = 'fileformat';
    case DuplicateFile = 'duplicate';

    case ReplaceNotSelected = 'replace';
    case FixNotSelected = 'fix.checked';

    case MissingHeaderMeta = 'missing';
    case AuthorInvalid = 'authorinvalid';

    case CircularReference = 'circularreference';
    case BfcNotCcw = 'bfc';

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

    public function type(): CheckType
    {
        return CheckType::Error;
    }

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
            self::LeadingZeros => true,
            default => false,
        };
    }

    public function multiLineHeader(): ?string
    {
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
            self::DecimalPrecision,
            self::TrailingZeros,
            self::LeadingZeros => $this->description(),
            default => null,
        };
    }

    public function customLabel(): ?string
    {
        return match($this) {
            self::BfcNotCcw => 'BFC is not CCW',
            self::DecimalPrecision => 'Decimal precision exceeds 5',
            default => null,
        };
    }

    public function description(): string
    {
        return match($this) {
            self::InvalidFileFormat => 'Files can only be text or png images',
            self::DuplicateFile => 'A :value already exists with this name',
            self::ReplaceNotSelected => 'To submit a change to a part already on the Parts Tracker, you must check "Replace existing file(s)"',
            self::FixNotSelected => '"New version of official file(s)" must be checked to submit official part updates',
            self::MissingHeaderMeta => 'Required header META :value missing or invalid',
            self::AuthorInvalid => 'Author line invalid or listed author is not registered with the LDraw.org Library',
            self::CircularReference => 'Has a self referring Type 1 line',
            self::BfcNotCcw => 'All parts must be BFC CERTIFY CCW',
            self::LineInvalid,
            self::InvalidLineNumbers => 'Line :line invalid',
            self::InvalidLineType0 => 'Line :line, invalid META command or comment without //',
            self::InvalidLineColor => 'Line :line, color code not in LDConfig.ldr',
            self::InvalidColoredLines => 'Line :line, linetypes 2 and 5 should only be color 24',
            self::InvalidColor16 => 'Line :line, color code 16 not allowed for linetypes 2, 5',
            self::InvalidColor24 => 'Line :line, color code 24 not allowed for linetypes 1, 3, 4',
            self::RotationMatrixIsSingular => 'Line :line, singular rotation matrix',
            self::IdenticalPoints => 'Line :line, identical points',
            self::PointsColinear => 'Line :line, points are colinear (angle :value)',
            self::QuadNotConvex => 'Line :line, quad is concave or bowtie',
            self::QuadNotCoplanar => 'Line :line, quad is not coplanar (angle :value)',
            self::PartNameInvalid => 'Only characters a-z, 0-9, _ . and - are allowed in file names',
            self::NameAndFilenameNotEqual => 'Name: line (:value) does not match submitted filename',
            self::UnknownPartNumberName => 'Parts with unknown numbers no longer use "x" as a filename prefix',
            self::FlexSectionIncorrectSuffix => 'The name for Flexible_Section parts must end with kXX',
            self::InvalidDescription => 'Description line may not contain special characters',
            self::PatternNotInDescription => 'Pattern part description must end with "Pattern" or have a "Colour Combination" keyword',
            self::NoTildeForSubpart => 'Subpart descriptions must begin with "~"',
            self::NoEqualsForAlias => 'Alias part descriptions must begin with "="',
            self::NoPipeForThirdParty => 'Third party part descriptions must begin with "|"',
            self::NoTildeForMovedObsolete => 'Moved or Obsolete part descriptions must begin with "~"',
            self::ImproperObsolete => 'An obsolete part must have the category Obsolete and ~Obsolete file or (Obsolete) in the description',
            self::NameTypeMismatch => 'Path in Name: (:value) is invalid for !LDRAW_ORG part type (:type)',
            self::AliasNotInParts => 'Alias parts must have type Part or Shortcut',
            self::FlexSectionNotPart => 'Flexible Section parts must be of type Part',
            self::LicenseNotLibraryApproved => 'LICENSE line does not specify an approved license',
            self::CategoryInvalid => 'Category is not valid',
            self::NoSetKeywordForPattern => 'Pattern parts and sticker shortcuts must have a "Set <setnumber>", "CMF", or "Build-A-Minifigure" keyword',
            self::DecimalPrecision => 'Decimal precision exceeds 5 significant places',
            self::TrailingZeros => 'Numbers cannot have trailing zeros (except one zero immediately after a decimal point)',
            self::LeadingZeros => 'Numbers cannot have leading zeros (except one zero immediately before a decimal point)',
            self::HistoryInvalid => 'Invalid history line(s)',
            self::HistoryAuthorNotRegistered => 'History has an author who is not registered with the LDraw.org Library',
            self::NewPartIsPhysicalColor => 'New Physical_Color parts are no longer accepted'
        };
    }
}
