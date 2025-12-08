<?php

namespace App\Console\Commands;

use App\Events\PartRenamed;
use App\Models\Part\Part;
use App\Models\User;
use App\Services\LDraw\Managers\Part\PartManager;
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
    public function handle(PartManager $manager): void
    {
        $parts = Part::partsFolderOnly()
            ->whereLike('filename', 'parts/11477d___.dat')
            ->orderBy('filename')
            ->get();
        $suffixes = '123456789abcedfghijklmn';
        $suffixIndex = 0;
        $user = User::find(1);
        foreach($parts as $part) {
            $newName = '11477d1' . $suffixes[$suffixIndex] . '.dat';
            $oldname = $part->filename;
            $manager->movePart($part, $newName, $part->type);
            $part->refresh();
            PartRenamed::dispatch($part, $user, $oldname, $part->filename);
            $suffixIndex++;
        }

    }
}
