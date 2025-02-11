<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::create(['name' =>'LDraw Member']);
        $perms = [];
        $perms[] = Permission::create(['name' =>'member.poll.vote']);
        $perms[] = Permission::create(['name' =>'member.access']);
        $role->givePermissionTo($perms);

        $role = Role::create(['name' =>'Membership Admin']);
        $perms = [];
        $perms[] = Permission::create(['name' =>'member.poll.manage']);
        $role->givePermissionTo($perms);

        /*
        Part::query()->update(['rebrickable' => null]);
        $rb = app(\App\LDraw\Rebrickable::class);
        Part::partsFolderOnly()
            ->where('description', 'NOT LIKE', '~%')
            ->where('description', 'NOT LIKE', '\_%')
            ->where('description', 'NOT LIKE', '|%')
            ->where('description', 'NOT LIKE', '%(Obsolete)%')
            ->whereRelation('category', 'category', '<>', 'Sticker')
            ->whereRelation('category', 'category', '<>', 'Moved')
            ->lazy()
            ->each(function (Part $part) use ($rb) {
                $number = basename($part->filename, '.dat');
                $rb_num = Str::lower($part->keywords()->where('keyword', 'LIKE', "Rebrickable %")->first()?->keyword);
                $bl_num = Str::lower($part->keywords()->where('keyword', 'LIKE', "Bricklink %")->first()?->keyword);
                $rb_part = $rb->getParts(['ldraw_id' => $number])->first();
                if (is_null($rb_part) && Str::startsWith($rb_num, 'rebrickable')) {
                    $rb_num = Str::chopStart(Str::lower($rb_num), 'rebrickable ');
                    $rb_part = $rb->getPart($rb_num)->first();
                }
                if (is_null($rb_part) && Str::startsWith($bl_num, 'bricklink')) {
                    $bl_num = Str::chopStart(Str::lower($bl_num), 'bricklink ');
                    $rb_part = $rb->getParts(['bricklink_id' => $number])->first();
                }
                if (is_null($rb_part)) {
                    $this->info($number, $rb_num, $bl_num);
                    return;
                }
                $part->rebrickable = $rb_part;
                $part->save();
            });
        */
    }
}
