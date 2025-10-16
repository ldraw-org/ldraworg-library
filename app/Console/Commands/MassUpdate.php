<?php

namespace App\Console\Commands;

use App\Models\Part\PartHistory;
use App\Models\User;
use Illuminate\Console\Command;

class MassUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:mass-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A command to do focused mass updates';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $kjm = User::firstWhere('realname', 'Kyle J. Mcdonald');
        $csc = User::firstWhere('name', 'Deckard');
        $unknown = User::find(290);
        $hist = PartHistory::where('user_id', 290)
            ->each(function (PartHistory $h) use ($kjm, $csc) {
                if ($h->comment == 'BFC Certification') {
                    $h->user_id = $kjm->id;
                    $h->save();
                } else {
                    $h->user_id = $csc->id;
                    $h->save();
                }
                $h->part->has_minor_edit = true;
                $h->part->generateHeader();
                $h->load('part');
                $this->info($h->part->header);
            });
    }
}
