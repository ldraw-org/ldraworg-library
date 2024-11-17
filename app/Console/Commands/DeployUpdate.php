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
        $parts = Part::where('filename', 'LIKE', "parts/u____%.dat")->get();
        foreach ($parts as $part) {
            $result = preg_match('/parts\/u([0-9]{4}).*\.dat/', $part->filename, $matches);
            if ($result) {
                $number = $matches[1];
                $unk = UnknownPartNumber::firstOrCreate(
                    ['number' => $number],
                    ['user_id' => $part->user->id]
                );
                if ($part->user_id !== $unk->user_id) {
                    $unk->user_id = $part->user_id;
                    $unk->save();
                }
                $part->unknown_part_number()->associate($unk);
                $part->save();
            }
        }
    }
}
