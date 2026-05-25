<?php

namespace App\Console\Commands;

use App\Enums\PartStatus;
use App\Models\Part\Part;
use Illuminate\Console\Command;

class OfficialCleanup extends Command
{
    protected $signature = 'lib:official-cleanup';

    protected $description = 'Official Library Cleanup';

    public function handle(): void
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
