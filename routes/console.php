<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('food:cancel-expired')->everyMinute();
Schedule::command('decoration:cancel-expired')->everyMinute();
Schedule::command('party:cancel-expired')->everyMinute();
Schedule::command('hall-bookings:cancel-expired')->everyMinute();