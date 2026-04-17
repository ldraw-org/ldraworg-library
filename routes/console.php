<?php

use App\Jobs\SendDailyDigests;
use App\Jobs\UpdateRebrickableStickerParts;
use App\Jobs\UpdateTrackerHistory;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:prune-batches')->daily();
Schedule::job(new SendDailyDigests())->dailyAt('01:30')->environments(['production']);
Schedule::job(new UpdateTrackerHistory())->daily();
Schedule::command('lib:daily-maintenance')->dailyAt('02:00');
Schedule::job(new UpdateRebrickableStickerParts)->weeklyOn(1);
