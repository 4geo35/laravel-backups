<?php

namespace GIS\LaravelBackups\Helpers;

use Illuminate\Support\Str;
use ZipArchive;
class ZipActionsManager
{
    /**
     * @var null|self
     */
    private $zipArchive = null;
    private ?string $path = null;
    private string $skip_mode = 'NONE';
    private static array $zipStatusCodes = [
        ZipArchive::ER_OK => 'No error',
        ZipArchive::ER_MULTIDISK => 'Multi-disk zip archives not supported',
        ZipArchive::ER_RENAME => 'Renaming temporary file failed',
        ZipArchive::ER_CLOSE => 'Closing zip archive failed',
        ZipArchive::ER_SEEK => 'Seek error',
        ZipArchive::ER_READ => 'Read error',
        ZipArchive::ER_WRITE => 'Write error',
        ZipArchive::ER_CRC => 'CRC error',
        ZipArchive::ER_ZIPCLOSED => 'Containing zip archive was closed',
        ZipArchive::ER_NOENT => 'No such file',
        ZipArchive::ER_EXISTS => 'File already exists',
        ZipArchive::ER_OPEN => 'Can\'t open file',
        ZipArchive::ER_TMPOPEN => 'Failure to create temporary file',
        ZipArchive::ER_ZLIB => 'Zlib error',
        ZipArchive::ER_MEMORY => 'Malloc failure',
        ZipArchive::ER_CHANGED => 'Entry has been changed',
        ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
        ZipArchive::ER_EOF => 'Premature EOF',
        ZipArchive::ER_INVAL => 'Invalid argument',
        ZipArchive::ER_NOZIP => 'Not a zip archive',
        ZipArchive::ER_INTERNAL => 'Internal error',
        ZipArchive::ER_INCONS => 'Zip archive inconsistent',
        ZipArchive::ER_REMOVE => 'Can\'t remove file',
        ZipArchive::ER_DELETED => 'Entry has been deleted'
    ];

    public function __construct($zipFile = null)
    {
        if ($zipFile) {
            $this->open($zipFile);
        }
    }

    public function open(string $zipFile)
    {

    }

    /**
     * @param string $zipFile
     * @param bool $overwrite
     * @return $this
     * @throws \Exception
     */
    public function create(string $zipFile, bool $overwrite = false): self
    {
        if ($overwrite && ! $this->check($zipFile)) {
            $overwrite = false;
        }

        $overwrite = filter_var($overwrite, FILTER_VALIDATE_BOOLEAN, [
            "options" => [
                "default" => false,
            ]
        ]);

        try {
            if ($overwrite) {
                $this->setArchive(self::openZipFile($zipFile, ZipArchive::OVERWRITE));
            } else {
                $this->setArchive(self::openZipFile($zipFile, ZipArchive::CREATE));
            }
        } catch (\Exception $exception) {
            throw $exception;
        }

        return $this;
    }

    public function check(string $zipFile): bool
    {
        try {
            $zip = self::openZipFile($zipFile, ZipArchive::CHECKCONS);
            $zip->close();
        } catch (\Exception $ex) {
            return false;
        }
        return true;
    }

    /**
     * @param $filePath
     * @param $flatroot
     * @return $this
     * @throws \Exception
     */
    public function add($filePath, $flatroot = false): self
    {
        if (empty($filePath)) {
            throw new \Exception(self::getStatus(ZipArchive::ER_NOENT));
        }

        $flatroot = filter_var($flatroot, FILTER_VALIDATE_BOOLEAN, [
            "options" => [
                "default" => false
            ]
        ]);

        try {
            if (is_array($filePath)) {
                foreach ($filePath as $file) {
                    $this->addItem($file, $flatroot);
                }
            } else {
                $this->addItem($filePath, $flatroot);
            }
        } catch (\Exception $exception) {
            throw $exception;
        }

        return $this;
    }

    public function close(): void
    {
        if ($this->zipArchive->close() === false) {
            throw new \Exception(self::getStatus($this->zipArchive->status));
        }
    }

    final public function setArchive(ZipArchive $zip) {
        $this->zipArchive = $zip;
        return $this;
    }

    /**
     * @param string $file
     * @param bool $flatroot
     * @param string|null $base
     * @return void
     * @throws \Exception
     */
    private function addItem(string $file, bool $flatroot = false, string $base = null): void
    {
        if ($this->path && !Str::startsWith($file, $this->path)) {
            $file = $this->path . $file;
        }

        $realFile = str_replace('\\', '/', realpath($file));
        $realName = basename($realFile);

        if (! is_null($base)) {
            if ($realName[0] == '.' && in_array($this->skipMode, ['HIDDEN', 'ALL'])) {
                return;
            }

            if ($realName[0] == '.' && @$realName[1] == "_" && in_array($this->skip_mode, ["AWERAM", "ALL"])) {
                return;
            }
        }

        if (is_dir($realFile)) {
            if (! $flatroot) {
                $folderTarget = is_null($base) ? $realName : $base . $realName;
                $newFolder = $this->zipArchive->addEmptyDir($folderTarget);
                if ($newFolder === false) {
                    throw new \Exception(self::getStatus($this->zipArchive->status));
                }
            } else {
                $folderTarget = null;
            }

            foreach (new \DirectoryIterator($realFile) as $path) {
                if ($path->isDot()) continue;

                $fileReal = $path->getPathName();
                $base = is_null($folderTarget) ? null : ($folderTarget . '/');

                try {
                    $this->addItem($fileReal, false, $base);
                } catch (\Exception $exception) {
                    throw $exception;
                }
            }
        } else if (is_file($realFile)) {
            $fileTarget = is_null($base) ? $realName : $base . $realName;
            $addFile = $this->zipArchive->addFile($realFile, $fileTarget);
            if ($addFile === false) {
                throw new \Exception(self::getStatus($this->zipArchive->status));
            }
        } else {
            return;
        }
    }

    /**
     * @param string $zipFile
     * @param $flags
     * @return ZipArchive
     * @throws \Exception
     */
    private static function openZipFile(string $zipFile, $flags = null): ZipArchive
    {
        $zip = new ZipArchive();

        if (is_null($flags)) {
            $open = $zip->open($zipFile);
        } else {
            $open = $zip->open($zipFile, $flags);
        }

        if ($open !== true) {
            throw new \Exception(self::getStatus($open));
        }

        return $zip;
    }

    private static function getStatus(int $code): string
    {
        if (array_key_exists($code, self::$zipStatusCodes)) {
            return self::$zipStatusCodes[$code];
        } else {
            return sprintf("Unknown status %s", $code);
        }
    }
}
