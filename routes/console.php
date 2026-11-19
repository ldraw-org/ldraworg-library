<?php

use App\Jobs\SendDailyDigests;
use App\Jobs\SyncRebrickableData;
use App\Jobs\UpdateTrackerHistory;
use Illuminate\Support\Facades\Schedule;

Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('queue:prune-batches')->daily();
Schedule::job(new SendDailyDigests())->dailyAt('01:30')->environments(['production']);
Schedule::job(new UpdateTrackerHistory())->daily();
Schedule::command('lib:daily-maintenance')->dailyAt('02:00');
// Production only, so development servers never use up the once-a-day Rebrickable download
Schedule::job(new SyncRebrickableData())->weeklyOn(1, '03:00')->environments(['production']);
