<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Send WhatsApp reminders every hour for appointments in the next 24 hours
Schedule::command('appointments:send-reminders --hours=24')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Reset WhatsApp monthly message quotas on the 1st of each month
Schedule::command('whatsapp:reset-quotas')
    ->monthlyOn(1, '00:00')
    ->withoutOverlapping();
