<?php

use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Permanently purge archived (soft-deleted) records older than 6 months every day.
Schedule::command('model:prune', [
    '--model' => [
        LibraryMember::class,
        LibraryPersonnel::class,
        User::class,
    ],
])->daily();
