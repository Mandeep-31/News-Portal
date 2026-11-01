<?php

use App\Console\Commands\CleanupExpiredSoftDeletes;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:cleanup-expired-soft-deletes', function () {
    $this->call(CleanupExpiredSoftDeletes::class);
})->purpose('Permanently delete soft-deleted records older than 30 days.');
