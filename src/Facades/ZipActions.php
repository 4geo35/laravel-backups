<?php

namespace GIS\LaravelBackups\Facades;

use GIS\LaravelBackups\Helpers\ZipActionsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ZipActionsManager open($zipFile)
 * @method static ZipActionsManager create($zipFile)
 * @method static ZipActionsManager check($zipFile)
 *
 * @see ZipActionsManager
 */
class ZipActions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return "zip-actions";
    }
}
