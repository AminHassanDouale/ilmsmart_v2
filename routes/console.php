<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-update subscription statuses daily
Schedule::command('subscriptions:check-expiry')->daily();

// Send daily attendance reminders
Schedule::command('notifications:attendance-reminder')->dailyAt('07:00');
