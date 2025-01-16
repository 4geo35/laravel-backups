<?php

namespace GIS\LaravelBackups\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RestoreDataBaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "restore:db {table?} {--file=" . BackupDataBaseCommand::FILE_NAME . "}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore application database';

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
        $file = $this->option("file");
        if (! Storage::disk("backups")->exists($file)) {
            $this->error("File not found");
            Log::error("Data Base file not found");
            return;
        }

        $password = $this->password;
        $db = $this->database;
        if ($table = $this->argument("table")) {
            $password = "";
            $db .= " $table";
        }

        $process = Process::fromShellCommandline(sprintf(
            'mysql -u%s -p%s --default-character-set utf8 %s < %s',
            $this->username,
            $password,
            $db,
            backup_path($file)
        ));

        try {
            $process->mustRun();

            Storage::disk("backups")->delete($file);
            $this->info("The restore has been processed successfully");
        } catch (ProcessFailedException $exception) {
            $this->error("The backup process has been failed");
            $this->info($exception->getMessage());
            Log::error("The backup of data base process has been failed");
        }
    }
}
