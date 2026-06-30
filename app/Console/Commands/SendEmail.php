<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Mail\DailyDigest;
use App\Mail\TestEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEmail extends Command
{
    protected $signature = 'lib:test-email {user} {--daily}';

    protected $description = 'Send a test or digest email to a user';

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
