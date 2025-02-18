<?php

namespace GIS\LaravelBackups;

use Aws\Sdk;
use GIS\LaravelBackups\Console\Commands\BackupApplicationCommand;
use GIS\LaravelBackups\Console\Commands\BackupDataBaseCommand;
use GIS\LaravelBackups\Console\Commands\BackupStorageCommand;
use GIS\LaravelBackups\Console\Commands\PullApplicationCommand;
use GIS\LaravelBackups\Console\Commands\PushApplicationCommand;
use GIS\LaravelBackups\Console\Commands\RestoreApplicationCommand;
use GIS\LaravelBackups\Console\Commands\RestoreDataBaseCommand;
use GIS\LaravelBackups\Console\Commands\RestoreStorageCommand;
use GIS\LaravelBackups\Helpers\ZipActionsManager;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;

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

            PushApplicationCommand::class,
            PullApplicationCommand::class,
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
        $this->extendStorage();
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
        // Routes
        $this->loadRoutesFrom(__DIR__ . "/routes/api.php");
        $this->loadRoutesFrom(__DIR__ . "/routes/admin.php");
    }

    protected function extendStorage(): void
    {
        Storage::extend("yaS3Backups", function ($app, $config) {
            $configS3 = [
                "endpoint" => "https://storage.yandexcloud.net",
                "region" => $config['region'],
                "version" => "latest",
                "credentials" => [
                    "key" => $config["key"],
                    "secret" => $config["secret"],
                ],
            ];
            if (config("app.debug")) {
                $configS3['http'] = [
                    'verify' => false
                ];
            }
            $sdk = new Sdk($configS3);
            $s3 = $sdk->createS3();

            $adapter = new AwsS3V3Adapter($s3, $config["bucket"]);

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });
    }
}
