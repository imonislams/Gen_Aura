<?php

use App\Jobs\CloseStaleSessions;
use App\Jobs\PruneAnalyticsData;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CloseStaleSessions)->hourly();
Schedule::job(new PruneAnalyticsData)->daily();
