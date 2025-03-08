<?php

namespace App\Console\Commands;

use App\Enums\PartStatus;
use App\Models\Part\Part;
use Illuminate\Console\Command;

class OfficialCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:official-cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Official Library Cleanup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Part::official()->update([
            'part_status' => PartStatus::Official,
            'delete_flag' => 0,
            'has_minor_edit' => false,
            'missing_parts' => null,
            'manual_hold_flag' => 0,
            'marked_for_release' => false
        ]);
        Part::official()->each(function (Part $p) {
            $p->votes()->delete();
            $p->notification_users()->sync([]);
        });
    }
}
