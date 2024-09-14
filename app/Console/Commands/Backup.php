<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class Backup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:backup {--db-only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Makes a backup package';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $zip = new \ZipArchive();
        if (!Storage::disk('local')->directoryExists('backup')) {
            Storage::disk('local')->makeDirectory('backup');
        }

        $db = config('database.connections.mysql.database');
        $db_user = config('database.connections.mysql.username');
        $db_pw = config('database.connections.mysql.password');
        $db_host = config('database.connections.mysql.host');
        $db_port = config('database.connections.mysql.port');

        $db_backup = Storage::disk('local')->path('backup/db_backup.sql');
        $result = Process::forever()->run("mysqldump --user={$db_user} --password={$db_pw} --host={$db_host} --port={$db_port} $db > {$db_backup}");

        $this->info($result->errorOutput());

        if (!$this->option('db-only')) {
            $zipname = Storage::disk('local')->path('backup/backup.zip');

            $zip->open($zipname, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            $zip->addFile(base_path('.env'), '.env');
            $zip->addFile($db_backup, 'storage/app/backup/db_backup.sql');

            foreach (Storage::disk('local')->allFiles('deleted') as $file) {
                $zip->addFile(Storage::disk('local')->path($file), "storage/app/{$file}");
            }

            foreach (Storage::disk('library')->allFiles('official') as $file) {
                $zip->addFile(Storage::disk('library')->path($file), "storage/app/library/{$file}");
            }
            $zip->close();
            $zip->open($zipname);
            foreach (Storage::disk('library')->allFiles('omr') as $file) {
                $zip->addFile(Storage::disk('library')->path($file), "storage/app/library/{$file}");
            }
            $zip->close();
            $zip->open($zipname);
            foreach (Storage::disk('library')->allFiles('updates') as $file) {
                $zip->addFile(Storage::disk('library')->path($file), "storage/app/library/{$file}");
            }
            $zip->close();
        }
    }
}
