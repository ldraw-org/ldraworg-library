<?php

namespace App\Console\Commands;

use App\Models\LdrawColour;
use App\Models\Part\Part;
use App\Models\Part\UnknownPartNumber;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

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
        $csv = Reader::createFromPath(Storage::path('db/3dp.csv'));
        $csv->setHeaderOffset(0);
        $parts = Part::where('filename', 'LIKE', 'parts/u9___%.dat')->get();
        foreach ($csv->getRecords() as $unumber) {
            $user = User::firstWhere('name', $unumber['Author']);
            $part = $parts->first(fn (Part $p) => strpos($p->filename, "parts/{$unumber['Number']}") !== false);
            if (!is_null($user) && (is_null($part) || ($part->isUnofficial() && is_null($part->official_part)))) {
                $u = new UnknownPartNumber();
                $u->user()->associate($user);
                $u->number = substr($unumber['Number'], 1);
                $u->save();
                $this->info($user->name . " " . ($part?->filename ?? $unumber['Number']));
            }
        }
    }
}
