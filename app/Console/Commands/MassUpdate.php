<?php

namespace App\Console\Commands;

use App\Events\PartSubmitted;
use App\Models\Part\PartBody;
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
        $user = User::find(1);
        PartBody::with('part')
            ->lazy()
            ->each(function (PartBody $b) use ($user) {
                if (preg_match('~^0(?:\h+//)?\h*$~m', $b->body)) {
                    $body = $b->body;
                    $body = preg_replace('~^0(?:\h+//)?\h*$~m', '', $body);
                    $body = preg_replace('~\n{3,}~us', "\n\n", trim($body));
                    $b->body = $body;
                    $b->save();
                    if ($b->part->isOfficial()) {
                        $b->part->has_minor_edit = true;
                        $b->part->save();                  
                    } else {
                        $b->part->history()->create([
                            'user_id' => $user->id,
                            'comment' => 'Removed blank comments',
                        ]);
                        $b->part->generateHeader();
                        PartSubmitted::dispatch($b->part, $user, 'Removed blank comments');
                    }  
                }
            });
    }
}
