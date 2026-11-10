<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 */

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class StoragePath
{
    /**
     * Return the cache pathname for a materialized storage object.
     */
    public static function cachePath(string $path): string
    {
        return storage_path('framework/cache/'.sha1($path));
    }

    /**
     * Return a local pathname for a stored object, materializing remote disks
     * into the framework cache when necessary.
     */
    public static function for(string $path): string
    {
        $disk = Storage::disk(config('filesystems.default'));
        if (method_exists($disk, 'path')) {
            $localPath = $disk->path($path);
            if (is_file($localPath)) {
                return $localPath;
            }
        }
        $localPath = self::cachePath($path);
        if (! is_file($localPath)) {
            file_put_contents($localPath, $disk->get($path), LOCK_EX);
        }

        return $localPath;
    }
}
