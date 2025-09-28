<?php

use Illuminate\Support\Facades\Schedule;
use App\LDraw\ScheduledTasks\SendDailyDigest;
use App\LDraw\ScheduledTasks\UpdateTrackerHistory;

Schedule::command('queue:prune-batches')->daily();
Schedule::command('lib:refresh-csv')->everyFiveMinutes();
Schedule::call(new SendDailyDigest())->dailyAt('01:30')->environments(['production']);
Schedule::call(new UpdateTrackerHistory())->daily();
Schedule::command('lib:daily-maintenance')->daily();