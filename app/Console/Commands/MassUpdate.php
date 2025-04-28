<?php

namespace App\Console\Commands;

use App\Enums\PartError;
use App\Enums\VoteType;
use App\Events\PartSubmitted;
use App\LDraw\LDrawFile;
use App\LDraw\PartManager;
use App\LDraw\VoteManager;
use App\Models\Part\Part;
use App\Models\User;
use App\Settings\LibrarySettings;
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
    public function handle()
    {
        $pm = app(PartManager::class);

        $meta = preg_quote(implode("|", app(LibrarySettings::class)->allowed_body_metas));
        $meta_pattern = "#^\h?(0\h+)(?!{$meta})#ium";
        
        // Fix invalid meta
        Part::with('body')
            ->official()
            ->doesntHave('unofficial_part')
            ->hasError(PartError::InvalidLineType0)
            ->get()
            ->each(function (Part $part) use ($pm, $meta_pattern) {
                $part->body->body = preg_replace($meta_pattern, '0 // ', $part->body->body);
                $pattern = "/^\h?(0\h+\/\/)([^\h])/ium";
                $part->body->body = preg_replace($pattern, '0 // $2', $part->body->body);
                $part->body->save();
                $part->has_minor_edit = true;
                $part->save();
                $pm->checkPart($part);
            });

        // Remove scientific notation
        Part::with('body')
            ->official()
            ->doesntHave('unofficial_part')
            ->hasError(PartError::LineInvalid)
            ->get()
            ->each(function (Part $part) use ($pm) {
                $pattern = "/[0-9.-]+e-\d+/iu";
                $part->body->body = preg_replace($pattern, '0', $part->body->body);
                $part->body->save();
                $part->has_minor_edit = true;
                $part->save();
                $pm->checkPart($part);
            });
/*
        // Fix BFC to CCW
        $vm = app(VoteManager::class);
        $user = User::find(1);

        Part::with('body')
            ->official()
            ->doesntHave('unofficial_part')
            ->hasError(PartError::BfcNotCcw)
            ->get()
            ->each(function (Part $part) use ($pm, $vm, $user) {
                $upart = $pm->submit(LDrawFile::fromPart($part), $user)->first();
                PartSubmitted::dispatch($upart, $user);
                $upart->body->body = preg_replace(config('ldraw.patterns.line_type_3'), '3 $1 $8 $9 $10 $5 $6 $7 $2 $3 $4', $part->body->body);
                $upart->body->body = preg_replace(config('ldraw.patterns.line_type_4'), '4 $1 $11 $12 $13 $8 $9 $10 $5 $6 $7 $2 $3 $4', $part->body->body);
                $upart->body->save();

                $upart->bfc = 'CCW';
                $upart->history()->create([
                    'user_id' => 1,
                    'comment' => 'Changed winding to CCW',
                ]);
                $upart->load('history');
                $upart->generateHeader();
                $upart->save();
                $pm->checkPart($upart);
                $vm->castVote($upart, $user, VoteType::AdminFastTrack);
            });
*/
    }
}
