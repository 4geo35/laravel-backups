<?php

use Illuminate\Support\Facades\Route;
use GIS\LaravelBackups\Http\Controllers\Api\BackupController;

Route::middleware(["auth:api", "super-user"])
    ->prefix("api/backups")
    ->as("api.backups.")
    ->group(function () {
        Route::get("/", [BackupController::class, "index"])->name("index");
        Route::post("/{period}", [BackupController::class, "make"])->name("make");
        Route::put("/{period}", [BackupController::class, "restore"])->name("restore");
    });
