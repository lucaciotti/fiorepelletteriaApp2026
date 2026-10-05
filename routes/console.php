<?php

use App\Settings\WorkOrderSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$alertHour = '18:00';

try {
    $alertHour = app(WorkOrderSettings::class)->alert_hour ?: '18:00';
} catch (Throwable) {
    // Impostazioni non ancora disponibili (es. prima delle migrazioni): usa il default.
}

Schedule::command('workorders:check-open')
    ->dailyAt($alertHour)
    ->withoutOverlapping();
