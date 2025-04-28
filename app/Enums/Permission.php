<?php

namespace App\Enums;

enum Permission: string
{
    case PartSubmitRegular = 'part.submit.regular';
    case PartSubmitProxy = 'part.submit.proxy';
    case PartSubmitFix = 'part.submit.fix';

    case PartEditHeader = 'part.edit.header';
    case PartEditNumber = 'part.edit.number';

    case PartDelete = 'part.delete';

    case PartVoteCertify = 'part.vote.certify';
    case PartVoteCertifyAll = 'part.vote.certify.all';
    case PartVoteHold = 'part.vote.hold';
    case PartVoteAdminCertify = 'part.vote.admincertify';
    case PartVoteAdminCertifyAll = 'part.vote.admincertify.all';
    case PartVoteFastTrack = 'part.vote.fast-track';

    case PartFlagManualHold = 'part.flag.manual-hold';
    case PartFlagDelete = 'part.flag.delete';
    case PartFlagError = 'part.flag.error';

    case PartComment = 'part.comment';

    case PartOwnVoteHold = 'part.own.vote.hold';
    case PartOwnVoteCertify = 'part.own.vote.certify';
    case PartOwnComment = 'part.own.comment';
    case PartOwnEditHeader = 'part.own.edit.header';

    case LdconfigEdit = 'ldconfig.edit';

    case UserViewEmail = 'user.view.email';

    case UserAdd = 'user.add';
    case UserUpdate = 'user.update';
    case UserUpdateSuperuser = 'user.manage.superuser';
    case UserDelete = 'user.delete';

    case RoleAdd = 'role.add';
    case RoleModify = 'role.modify';
    case RoleDelete = 'role.delete';

    case EditFiles = 'edit.files';

    case LdrawMemberAccess = 'ldraw.member.access';

    case PollManage = 'polls.manage';
    case PollVote = 'polls.vote';

    case PartKeywordsManage = 'keywords.manage';

    case ReviewSummaryManage = 'review-summary.manage';

    case RoleManage = 'role.manage';
    case RoleManageSuperuser = 'role.manage.superuser';

    case DocumentManage = 'documentation.manage';
    case DocumentCategoryManage = 'document-category.manage';

    case TelescopeView = 'telescope.view';

    case AdminDashboardView = 'admin.dashboard.view';

    case SiteSettingsEdit = 'site.settings.edit';

    case PartReleaseCreate = 'release.create';

    case OmrModelSubmit = 'omr.model.submit';
    case OmrModelApprove = 'omr.model.approve';
    case OmrModelEdit = 'omr.model.edit';
}
