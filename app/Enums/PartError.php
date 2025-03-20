<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

enum PartError: string
{
    use CanBeOption;

    case MissingHeaderMeta = 'missing';
    case CircularReference = 'circularreference';
    case BfcNotCcw = 'bfc';
    case PreviewInvalid = 'previewinvalid';

    case LineInvalid = 'line.invalid';
    case InvalidLineType0 = 'line.invalidmeta';

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

    case NameTypeMismatch = 'type.path';
    case NewPartIsPhysicalColor = 'type.phycolor';
    case AliasNotInParts = 'type.alias';
    case FlexSectionNotPart = 'type.flex';

    case AuthorNotRegistered = 'author.registered';

    case LicenseNotLibraryApproved = 'license.approved';

    case CategoryInvalid = 'category.invalid';

    case NoSetKeywordForPattern = 'keyword.partternset';

    case HistoryInvalid = 'history.invalid';
    case HistoryAuthorNotRegistered = 'history.authorregistered';

    case NoCertifiedParents = 'tracker.nocertparents';
    case HasUncertifiedSubfiles = 'tracker.uncertsubs';
    case HasMissingSubfiles = 'tracker.missing';
    case AdminHold = 'tracker.adminhold';

}
