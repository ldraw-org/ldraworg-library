<?php

namespace App\Services\DB;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class DatabaseBackup
{
    protected TemporaryDirectory $tempDir;
    
    public function __construct()
    {
        $this->tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
    }

    protected function setConfigFile(): string
    {
        $db_port = config('database.connections.mysql.port');
        $db_host = config('database.connections.mysql.host');
        $db_user = config('database.connections.mysql.username');
        $db_pw = config('database.connections.mysql.password');
        $file = "[client]\nhost={$db_host}\nuser={$db_user}\npassword={$db_pw}\nport={$db_port}";
        $path = $this->tempDir->path(".my.cnf");
        file_put_contents($path, $file);
        
        return $path;
    }

    public function backup(): void
    {
        $db = config('database.connections.mysql.database');
        $dump_path = Storage::disk('local')->path('backup') . '/backup.sql';
        $dumpcommand = "mysqldump --defaults-extra-file=\"{$this->setConfigFile()}\" --add-drop-table --single-transaction --set-gtid-purged=off --no-tablespaces {$db} > {$dump_path}";
        Process::forever()->run($dumpcommand);
    }
    
    public function restore(): void
    {
        $db = config('database.connections.mysql.database');
        $dump_path = Storage::disk('local')->path('backup') . '/backup.sql';
        $import_command = "mysql --defaults-extra-file=\"{$this->setConfigFile()}\" {$db} < {$dump_path}";
        Process::forever()->run($import_command);
    }
}