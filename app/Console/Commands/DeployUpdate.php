<?php

namespace App\Console\Commands;

use App\Enums\PartType;
use App\Models\Part\PartRelease;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

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
        PartRelease::each(function (PartRelease $release) {
            $data = $release->part_data;
            if (!is_null($data)) {
                $release->total = Arr::get($data, 'total_files', 0);
                $release->new = Arr::get($data, 'new_files', 0);
                $release->new_of_type = Arr::mapWithKeys(
                   Arr::get($data, 'new_types', []),
                    function (array $part, int $key) {
                        if ($part['name'] == 'Part TEXMAP Image') {
                            $type = PartType::PartTexmap;
                        } else {
                            $type = PartType::tryFromDescription($part['name']);
                        }
                        return [$type->value => $part['count']];
                    } 
                );
                $release->moved = Arr::map(
                   Arr::get($data, 'moved_parts', []),
                    fn (array $part) => ['from' => $part['name'], 'to' => $part['movedto']]
                );
                $release->fixed = Arr::map(
                    Arr::get($data, 'fixed', []),
                    fn (array $part) => [
                        'name' => $part['name'], 
                        'description' => $part['decription']
                    ]
                    );
                $release->renamed = Arr::map(
                    Arr::get($data, 'rename', []),
                    fn (array $part) => [
                        'name' => $part['name'], 
                        'old' => $part['old_description'],
                        'new' => $part['decription']
                    ]
                );
                $release->save();
            }
        });
    }
}
