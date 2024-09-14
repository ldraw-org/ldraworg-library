<?php

namespace App\Console\Commands;

use App\Models\Part;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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
        foreach (Storage::files('db') as $file) {
            $id = basename($file, '.txt');
            if ($id == 'lib.sql' || $id == 'lib2.sql') {
                continue;
            }
            $this->info($id);
            $p = Part::find($id);
            $body = Storage::get($file);
            $p->body->body = $body;
            $p->body->save();
            app(\App\LDraw\PartManager::class)->loadSubpartsFromBody($p);
        }
    }
}
