<?php

namespace App\Console\Commands;

use App\Enums\License;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Console\Command;

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
        Part::lazy()->each(function (Part $p) {
            $p->type = PartType::from($p->part_type->type);
            if (!is_null($p->part_type_qualifier)) {
                $p->type_qualifier = PartTypeQualifier::from($p->part_type_qualifier->type);
            }
            $p->license = License::from($p->part_license->name);
            $p->save();
        });

        User::each(function (User $u) {
            $u->license = License::from($u->part_license->name);
            $u->save();
        });

    }
}
