<?php

namespace App\Jobs;

use App\Mail\DailyDigest;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendDailyDigests implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Use chunkById to keep memory usage low even if you have thousands of users
        User::whereHas('notification_parts', function ($q) {
                $q->whereHas('events', function ($qu) {
                    $qu->whereNull('part_release_id')
                        ->whereBetween('created_at', [now()->subDay()->startOfDay(), now()->startOfDay()]);
                });
            })
            ->where('is_legacy', false)
            ->where('is_synthetic', false)
            ->where('is_ptadmin', false)
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    // This dispatches a separate job to the 'jobs' table for every user
                    Mail::to($user)->queue(new DailyDigest($user));
                }
            });
    }
}
