<?php

namespace GIS\LaravelBackups\Console\Commands;

use GIS\LaravelBackups\Facades\ZipActions;
use GIS\LaravelBackups\Helpers\ZipActionsManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupStorageCommand extends Command
{
    const FILE_NAME = "public.zip";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:storage';

    protected ?ZipActionsManager $zip = null;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup public folder in storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (Storage::disk("backups")->exists(self::FILE_NAME)) {
            Storage::disk("backups")->delete(self::FILE_NAME);
        }

        try {
            $this->zip = ZipActions::create(backup_path(self::FILE_NAME));
        } catch (\Exception $exception) {
            $this->zip = null;
        }

        if (! $this->zip) {
            $this->error("Fail init archive");
            return;
        }
        try {
            $handle = opendir(backup_storage_path());
            $exceptionItems = ["livewire-tmp", ".gitignore"];
            if (config("fileable.thumbFolder")) $exceptionItems[] = config("fileable.thumbFolder");
            while (false !== ($entry = readdir($handle))) {
                if (!in_array($entry, $exceptionItems) && $entry !== "." && $entry !== "..") {
                    $this->zip->add(backup_storage_path($entry));
                }
            }
            closedir($handle);
            $this->zip->close();
        } catch (\Exception $exception) {
            $this->error("Error while generated archive");
            $this->zip->close();
        }
    }
}
