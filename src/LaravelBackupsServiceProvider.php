<?php

namespace GIS\LaravelBackups;

use GIS\LaravelBackups\Console\Commands\BackupApplicationCommand;
use GIS\LaravelBackups\Console\Commands\BackupDataBaseCommand;
use GIS\LaravelBackups\Console\Commands\BackupStorageCommand;
use GIS\LaravelBackups\Console\Commands\RestoreApplicationCommand;
use GIS\LaravelBackups\Console\Commands\RestoreDataBaseCommand;
use GIS\LaravelBackups\Console\Commands\RestoreStorageCommand;
use GIS\LaravelBackups\Helpers\ZipActionsManager;
use Illuminate\Support\ServiceProvider;

class LaravelBackupsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            BackupDataBaseCommand::class,
            RestoreDataBaseCommand::class,

            BackupStorageCommand::class,
            RestoreStorageCommand::class,

            BackupApplicationCommand::class,
            RestoreApplicationCommand::class,
        ]);
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
        // Facades
        $this->app->singleton("zip-actions", function () {
            return new ZipActionsManager;
        });
    }
}
