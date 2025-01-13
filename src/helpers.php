<?php

if (! function_exists("backup_path")) {
    function backup_path($path = ""): string
    {
        $folder = DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "backups";
        return app("path.storage") . ($path ? $folder . DIRECTORY_SEPARATOR . $path : $folder);
    }
}
