<?php

use Illuminate\Support\Facades\Route;
use GIS\LaravelBackups\Http\Controllers\Api\BackupController;

Route::middleware(["web", 'auth', "super-user"])
    ->prefix("admin/backups")
    ->as("admin.backups.")
    ->group(function () {
        Route::get("/", [BackupController::class, "download"])
            ->name("download");
    });
