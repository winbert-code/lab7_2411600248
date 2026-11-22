<?php

use App\Console\Kernel;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lab 7: daily automated inventory replenishment check (see app/Console/Kernel.php).
Kernel::schedule(Schedule::getFacadeRoot());
