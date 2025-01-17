<?php

namespace GIS\LaravelBackups\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PullApplicationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:pull {period=daily} {--to-current} {--folder=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull app zip by period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = $this->argument("period");
        $fileName = "{$period}.zip";
        $folder = BackupApplicationCommand::FOLDER;
        $currentFile = "{$folder}/{$fileName}";
        $s3Folder = $this->option("folder");
        if (empty($s3Folder)) $s3Folder = "";
        else $s3Folder .= "/";
        $s3File = $s3Folder . $fileName;
        $filePath = $this->option("to-current") ? $currentFile : $fileName;
        if (Storage::disk("yandex")->exists($s3File)) {
            Storage::disk("backups")->put(
                $filePath,
                Storage::disk("yandex")->get($s3File)
            );
            $this->info("{$fileName} downloaded");
        } else {
            $this->error("File not found");
        }
    }
}
