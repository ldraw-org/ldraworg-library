<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Mail\DailyDigest;
use App\Mail\TestEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:test-email {user} {--daily}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test or digest email to a user';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $rn = $this->argument('user');
        $user = User::firstWhere('name', $rn);
        if ($this->option('daily')) {
            Mail::to($user)->send(new DailyDigest($user));
        } else {
            Mail::to($user)->send(new TestEmail(now(), 'This is a test message from the Parts Tracker'));
        }
    }
}
