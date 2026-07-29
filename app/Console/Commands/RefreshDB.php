<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DB\DatabaseBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class RefreshDB extends Command
{
    protected $signature = 'lib:refresh-db';

    protected $description = 'Refresh the local DB';

    public function handle(DatabaseBackup $databaseBackup): void
    {
        if (app()->environment('local')) {
            $databaseBackup->restore();
            $this->call('migrate');
            if (config('ldraw.local_user_password') !== null) {
                $u = User::find(1);
                $u->password = Hash::make(config('ldraw.local_user_password'));
                $u->save();
            }
        } else {
            $this->info('This command cannot be run the the production environment');
        }
    }
}
