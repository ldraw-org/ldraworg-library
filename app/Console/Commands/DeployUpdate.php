<?php

namespace App\Console\Commands;

use App\Enums\PartType;
use App\Enums\PartStatus;
use App\Enums\Permission;
use App\Models\Part\Part;
use App\Models\Part\PartEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission as PermissionModel;

class DeployUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the app after update deployments';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $nameupdates = [
            'admin.view-dashboard' => Permission::AdminDashboardView,
            'member.access' => Permission::LdrawMemberAccess,
            'member.poll.manage' => Permission::PollManage,
            'member.poll.vote' => Permission::PollVote,
            'omr.create' => Permission::OmrModelSubmit,
            'omr.delete' => null,
            'omr.update' => Permission::OmrModelEdit,
            'part' => null,
            'part.keyword.edit' => Permission::PartKeywordsManage,
            'part.vote.fasttrack' => Permission::PartVoteFastTrack,
            'reviewsummary.manage' => Permission::ReviewSummaryManage,
            'role' => null,
            'user' => null,
            'user.add.nonadmin' => null,
            'user.modify' => Permission::UserUpdate,
            'user.modify.email' => null,
            'user.modify.role.nonadmin' => Permission::UserUpdateSuperuser
        ];
        foreach ($nameupdates as $name => $permission) {
            $model = PermissionModel::firstWhere('name', $name);
            if (is_null($model)) {
                continue;
            }
            if (is_null($permission)) {
                $model->delete();
            } else {
                $model->name = $permission->value;
                $model->save();
            }
        }

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value);
        }

    }
}
