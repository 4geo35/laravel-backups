<?php

namespace GIS\LaravelBackups;

use Illuminate\Support\ServiceProvider;

class LaravelBackupsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Export config
        $this->publishes([
            __DIR__ . "/config/laravel-backups.php" => config_path("laravel-backups.php")
        ], "config");

        // Добавить конфигурацию для файловой системы.
        app()->config['filesystems.disks.backups'] = [
            'driver' => 'local',
            'root' => backup_path(),
        ];
        app()->config['filesystems.disks.yandex'] = [
            'driver' => "yaS3Backups",
            "key" => config("laravel-backups.keyId"),
            'secret' => config("laravel-backups.keySecret"),
            'region' => config("laravel-backups.region"),
            'bucket' => config("laravel-backups.bucket"),
        ];
    }

    public function register(): void
    {
        // Configuration
        $this->mergeConfigFrom(
            __DIR__ . "/config/laravel-backups.php", "laravel-backups"
        );
    }
}
