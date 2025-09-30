<?php

use App\Jobs\UpdateLibraryCsv;
use Illuminate\Support\Facades\Schedule;
use App\Services\LDraw\ScheduledTasks\SendDailyDigest;
use App\Services\LDraw\ScheduledTasks\UpdateTrackerHistory;

Schedule::command('queue:prune-batches')->daily();
Schedule::call(new SendDailyDigest())->dailyAt('01:30')->environments(['production']);
Schedule::call(new UpdateTrackerHistory())->daily();
Schedule::command('lib:daily-maintenance')->daily();