<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;
use Illuminate\Support\Str;

enum PartError: string
{
    use CanBeOption;

    case MissingHeaderMeta = 'missing';
    case CircularReference = 'circularreference';
    case BfcNotCcw = 'bfc';
    case PreviewInvalid = 'previewinvalid';

    case LineInvalid = 'line.invalid';
    case InvalidLineType0 = 'line.invalidmeta';
    case InvalidLineColor = 'line.invalidcolor';
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

    case AuthorNotRegistered = 'author.registered';

    case LicenseNotLibraryApproved = 'license.approved';

    case CategoryInvalid = 'category.invalid';

    case NoSetKeywordForPattern = 'keywords.patternset';

    case HistoryInvalid = 'history.invalid';
    case HistoryAuthorNotRegistered = 'history.authorregistered';

    case TrackerNoCertifiedParents = 'tracker_hold.nocertparents';
    case TrackerHasUncertifiedSubfiles = 'tracker_hold.uncertsubs';
    case TrackerHasMissingSubfiles = 'tracker_hold.missing';
    case TrackerAdminHold = 'tracker_hold.adminhold';

    case WarningMinifigCategory = 'warning.minifigcategory';
    case WarningNotCoplanar = 'warning.notcoplaner';
    case WarningStickerColor = 'warning.stickercolor';

    public function type(): CheckType
    {
        return CheckType::tryFrom(Str::of($this->value)->replace('.', ' ')->words(1, '')->plural()) ?? CheckType::Error;
    }
}
