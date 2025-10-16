<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class RefreshDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:refresh-db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the local DB';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (app()->environment('local')) {
            $this->info('Copying production db backup');
            $db = config('database.connections.mysql.database');
            $prod_db = config('database.connections.mysql.prod_db');
            $db_port = config('database.connections.mysql.port');
            $db_host = config('database.connections.mysql.host');
            $db_user = config('database.connections.mysql.username');
            $db_pw = config('database.connections.mysql.password');
            $tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
            $file = "[client]\nhost={$db_host}\nuser={$db_user}\npassword={$db_pw}\nport={$db_port}";
            $path = $tempDir->path(".my.cnf");
            file_put_contents($path, $file);
            $dumpcommand = "mysqldump --defaults-extra-file=\"{$path}\" --add-drop-table --single-transaction --set-gtid-purged=off --no-tablespaces {$prod_db}";
            $import_command = "mysql --defaults-extra-file=\"{$path}\" {$db}";
            $result = Process::forever()->run("{$dumpcommand} | {$import_command}");
            $this->info($result->output());
            $this->info($result->errorOutput());
            $this->call('migrate');
        } else {
            $this->info('This command cannot be run the the production environment');
        }
    }
}
