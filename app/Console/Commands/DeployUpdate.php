<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use App\Models\Part;

class DeployUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update';

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
        Part::each(function(part $p) {
            if ($p->minor_edit_flag) {
                $p->minor_edit_data = ['license' => 'CC BY 2.0 to CC BY 4.0'];
                $p->save();
            }
        });
    }
}
