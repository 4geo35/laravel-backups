<?php

namespace GIS\LaravelBackups\Console\Commands;

use GIS\LaravelBackups\Facades\ZipActions;
use GIS\LaravelBackups\Helpers\ZipActionsManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupApplicationCommand extends Command
{
    const FOLDER = "current";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:app {period=daily} {--folder=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup app files by period';

    protected ?ZipActionsManager $zip = null;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->callSilent("backup:storage");
        if (! Storage::disk("backups")->exists(BackupStorageCommand::FILE_NAME)) {
            $this->error("Backup storage failed");
            return;
        }

        $this->callSilent("backup:db");
        if (! Storage::disk("backups")->exists(BackupDataBaseCommand::FILE_NAME)) {
            $this->error("Backup database failed");
            return;
        }

        $period = $this->argument("period");
        $fileName = "{$period}.zip";

        if (Storage::disk("backups")->exists($fileName)) {
            Storage::disk("backups")->delete($fileName);
        }

        try {
            $this->zip = ZipActions::create(backup_path($fileName));
        } catch (\Exception $exception) {
            $this->zip = null;
        }

        if (! $this->zip) {
            $this->error("Fail create archive");
            return;
        }

        try {
            $this->zip->add([
                backup_path(BackupDataBaseCommand::FILE_NAME),
                backup_path(BackupStorageCommand::FILE_NAME),
            ]);
            $this->zip->close();

            Storage::disk("backups")->delete([
                BackupDataBaseCommand::FILE_NAME,
                BackupStorageCommand::FILE_NAME,
            ]);

            $folder = self::FOLDER;
            $path = "{$folder}/{$fileName}";

            if (Storage::disk("backups")->exists($path)) {
                Storage::disk("backups")->delete($path);
            }
            Storage::disk("backups")->move($fileName, $path);

            // Cloud
            if (
                ! empty(config("laravel-backups.keyId")) &&
                ! empty(config("laravel-backups.keySecret")) &&
                ! empty(config("laravel-backups.bucket")) &&
                ! empty(config("laravel-backups.folder"))
            ) {
                $s3Folder = $this->option("folder");
                if (empty($s3Folder)) {
                    $s3Folder = config("laravel-backups.folder");
                }
                // TODO: push
            }

            $this->info("Backup {$period} created successfully");
        } catch (\Exception $exception) {
            $this->error("Error while generated archive");
            $this->line($exception->getMessage());
        }
    }
}
