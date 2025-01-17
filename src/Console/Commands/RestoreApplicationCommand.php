<?php

namespace GIS\LaravelBackups\Console\Commands;

use GIS\LaravelBackups\Facades\ZipActions;
use GIS\LaravelBackups\Helpers\ZipActionsManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RestoreApplicationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restore:app {period=daily} {--from-current} {--folder=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore app files by period';

    protected ?ZipActionsManager $zip = null;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = $this->argument("period");
        $fileName = "{$period}.zip";

        if ($this->option("from-current")) {
            $folder = BackupApplicationCommand::FOLDER;
            $path = "{$folder}/{$fileName}";
            if (Storage::disk("backups")->exists($path)) {
                Storage::disk("backups")->copy($path, $fileName);
            }
        } else {
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
                // TODO: pull
            }
        }

        if (! Storage::disk("backups")->exists($fileName)) {
            $this->error("File not found");
            return;
        }

        try {
            $this->zip = ZipActions::open(backup_path($fileName));
        } catch (\Exception $exception) {
            $this->zip = null;
        }

        if (! $this->zip) {
            $this->error("Fail to open archive");
            Log::error("Fail to open application archive");
            return;
        }

        try {
            $this->zip->extract(backup_path());
            $this->zip->close();
            Storage::disk("backups")->delete($fileName);

            $this->callSilent("restore:db");
            $this->callSilent("restore:storage");

            $this->callSilent("cache:clear");

            $this->info("Application successfully restored");
        } catch (\Exception $exception) {
            $this->error("Fail to extract archive. Need manually extraction.");
            Log::error("Fail to extract application archive. Need manually extraction.");
        }
    }
}
