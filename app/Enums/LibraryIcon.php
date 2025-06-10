<?php

namespace App\Enums;

enum LibraryIcon: string
{
    case Comment = 'mdi-comment';
    case File = 'mdi-file';
    case HeaderEdit = 'mdi-text-box-edit';
    case Rename = 'mdi-file-export';
    case Delete = 'mdi-recycle';
    case Release = 'mdi-school';
    case CancelVote = 'mdi-arrow-u-left-top';
    case AdminReview = 'mdi-clipboard-check';
    case AdminFastTrack = 'mdi-truck-fast';
    case Certify = 'mdi-check';
    case Error = 'mdi-close-octagon';
    case MenuDown = 'mdi-menu-down';
    case MenuRight = 'mdi-menu-right';
    case Official = 'mdi-seal-variant';
    case NotReleaseable = 'mdi-file-document-remove';
    case UnofficialPartStatus = 'mdi-square-rounded';
    case BreadcrumbsSeparater = 'mdi-chevron-double-right';
    case UserNotification = 'mdi-bell';
    case PartFlag = 'mdi-flag';
    case ExternalSite = 'mdi-open-in-new';
    case LinkOn = 'mdi-link-variant';
    case LinkOff = 'mdi-link-variant-off';
    case PartFix = 'mdi-tools';
    case UserLibraryAdmin = 'mdi-crown';
    case UserSeniorReviewer = 'mdi-account-school';
    case UserHeaderEditor = 'mdi-account-plus';
    case UserPartReviewer = 'mdi-account-check';
    case UserPartAuthor = 'mdi-account-edit';
    case Alert = 'mdi-alert';
    case Info = 'mdi-information-slab-circle';
    case UserVote = 'mdi-account-circle';
    case ViewerRefresh = 'mdi-refresh';
    case ViewerStudLogo = 'mdi-toy-brick';
    case ViewerHarlequin = 'mdi-brush';
    case ViewerBfc = 'mdi-flip-to-back';
    case ViewerShowAxis = 'mdi-axis-arrow';
    case ViewerPhoto = 'mdi-camera';

    case PageFirst = 'mdi-page-first';
    case PageLast = 'mdi-page-last';
    case PageNext = 'mdi-chevron-right';
    case PagePrevious = 'mdi-chevron-left';
    case TextConstraint = 'mdi-text-recognition';
    case RelationshipConstraint = 'mdi-arrow-expand-all';
    case BooleanConstraint = 'mdi-help-circle';
    case AuthorConstraint = 'mdi-account-group';
    case CategoryConstraint = 'mdi-shape-plus';
    case KeywordsConstraint = 'mdi-tag-multiple';
    case License = 'mdi-creative-commons';
    case Select = 'mdi-unfold-more-horizontal';
    case Help = 'mdi-help-box';
    case DateSelect = 'mdi-calendar-range';
    case TableFalse = 'mdi-close-circle-outline';
    case TableTrue = 'mdi-check-circle-outline';
    case TableFilter = 'mdi-filter';
    case TableSortDesc = 'mdi-chevron-down';
    case TableSortAsc = 'mdi-chevron-up';
}
