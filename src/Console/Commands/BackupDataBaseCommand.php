<?php

namespace GIS\LaravelBackups\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BackupDataBaseCommand extends Command
{
    const FILE_NAME = "backup.sql";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "backup:db {table?} {--file=" . self::FILE_NAME . "}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup application database';

    protected string $username;
    protected string $password;
    protected string $database;

    public function __construct()
    {
        parent::__construct();

        $this->username = config("database.connections.mysql.username");
        $this->password = config("database.connections.mysql.password");
        $this->database = config("database.connections.mysql.database");
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $password = $this->password;
        $db = $this->database;
        if ($table = $this->argument("table")) {
            $password = "";
            $db .= " $table";
        }

        $file = $this->option("file");
        if (Storage::disk("backups")->exists($file)) {
            Storage::disk("backups")->delete($file);
        }

        $process = Process::fromShellCommandline(sprintf(
            "mysqldump -u%s -p%s --default-character-set=utf8 --result-file=%s %s --ignore-table=%s --ignore-table=%s --ignore-table=%s",
            $this->username,
            $password,
            backup_path($file),
            $db,
            "$db.failed_jobs",
            "$db.jobs",
            "$db.thumb_images",
        ));

        try {
            $process->mustRun();
            $this->info("The backup has been processed successfully");
        } catch (ProcessFailedException $exception) {
            $this->error("The backup process has been failed");
            $this->info($exception->getMessage());
            Log::info($exception->getMessage());
        }
    }
}
