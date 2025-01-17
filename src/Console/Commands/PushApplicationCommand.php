<?php

namespace GIS\LaravelBackups\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PushApplicationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:push {period=daily} {--from-current} {--folder=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push app zip by period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = $this->argument("period");
        $fileName = "{$period}.zip";
        $folder = BackupApplicationCommand::FOLDER;
        $currentPath = "{$folder}/{$fileName}";
        $s3Folder = $this->option("folder");
        if (empty($s3Folder)) $s3Folder = "";
        else $s3Folder .= "/";

        if (
            $this->option("from-current") &&
            Storage::disk("backups")->exists($currentPath)
        ) {
            try {
                Storage::disk("backups")->copy($currentPath, $fileName);
            } catch (\Exception $exception) {
                $this->error("File already exists");
            }
        }

        if (! Storage::disk("backups")->exists($fileName)) {
            $this->error("File not found");
            return;
        }

        try {
            Storage::disk("yandex")->put(
                $s3Folder . $fileName,
                Storage::disk("backups")->get("$fileName")
            );
            if (
                $this->option("from-current") &&
                Storage::disk("backups")->exists($currentPath)
            ) {
                Storage::disk("backups")->delete($currentPath);
            }
            Storage::disk("backups")->delete($fileName);
        } catch (\Exception $exception) {
            $this->line($exception->getMessage());
        }
    }
}
