<?php

namespace App\Console\Commands;

use App\Events\PartSubmitted;
use App\Models\Part\Part;
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
          // Step 1: Initialize array to track Part IDs that need updating
            $updatedPartIds = [];
            
            // Step 2: Stream matching PartBodies using cursor and eager-load part
            $partBodies = PartBody::with('part')
                ->whereRaw("body REGEXP '(^|\\n)\\s*0(\\s*//)?\\s*($|\\n)'")
                ->cursor();
            
            // Step 3: Iterate efficiently
            foreach ($partBodies as $partBody) {
                // Replace lines matching "0" or "0 //" with a blank line
                $updatedBody = preg_replace('/^\s*0(\s*\/\/)?\s*$/m', "\n", $partBody->body);
                // 2. Squish multiple consecutive blank lines into a single blank line
                $updatedBody = preg_replace("/(\n\s*){2,}/", "\n\n", $updatedBody);
            
                // 3. Trim leading/trailing whitespace
                $updatedBody = trim($updatedBody);
            
                // Only update if the body actually changed
                if ($updatedBody !== $partBody->body) {
                    $partBody->body = $updatedBody;
                    $partBody->save();
            
                    // Track associated Part if it exists and needs update
                    if ($partBody->part && !$partBody->part->has_minor_edit) {
                        $updatedPartIds[$partBody->part->id] = true;
                    }
                }
            }
            
            // Step 4: Batch update all affected Parts in a single query
            if (count($updatedPartIds) > 0) {
                Part::whereIn('id', array_keys($updatedPartIds))
                    ->update(['has_minor_edit' => true]);
            }

            // Track Parts that need updates
            $updatedPartIds = [];

            $keywords = PartKeyword::with('parts')->cursor();

            foreach ($keywords as $keyword) {
                $original = $keyword->keyword;
                $updated  = Str::ucfirst($original); // Multibyte-safe

                if ($updated !== $original) {
                    $keyword->keyword = $updated;
                    $keyword->save();

                    foreach ($keyword->parts as $part) {
                        $updatedPartIds[$part->id] = true;
                    }
                }
            }
            
            // Check if there are any affected Parts
            if (count($updatedPartIds) > 0) {
                // Batch update has_minor_edit
                Part::whereIn('id', array_keys($updatedPartIds))
                    ->update(['has_minor_edit' => true]);

                // Call generateHeader for each affected Part
                Part::whereIn('id', array_keys($updatedPartIds))
                    ->get()
                    ->each
                    ->generateHeader();
            }
    }
}
