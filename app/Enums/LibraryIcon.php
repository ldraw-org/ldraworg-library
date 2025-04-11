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
    case AdminCertify = 'mdi-clipboard-check';
    case AdminFastTrack = 'mdi-truck-fast';
    case Certify = 'mdi-check';
    case Error = 'mdi-close-octagon';
    case MenuDown = 'mdi-menu-down';
    case MenuRight = 'mdi-menu-right';
    case Official = 'mdi-seal-variant';
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
}