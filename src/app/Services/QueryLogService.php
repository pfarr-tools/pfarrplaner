<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 *
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Class QueryLogService
 */
class QueryLogService
{
    public const SEPARATOR = PHP_EOL;

    /** @var string */
    protected static $logFile = '';

    /**
     * Get the path to the query log
     *
     * @param  string  $log  Optional suffix for the query log file name.
     */
    public static function file($log = ''): string
    {
        if (self::$logFile != '') {
            $file = self::$logFile;
        } else {
            $file = self::$logFile = storage_path('/logs/query.log'.($log ? '.'.$log : ''));
        }

        return $file;
    }

    /**
     * Append an encrypted query and restore cross-container write permissions.
     *
     * @param  string  $query  SQL query to record.
     * @return void
     */
    public static function put($query)
    {
        $file = self::file();
        File::append($file, time().'|||||||'.Crypt::encryptString($query).self::SEPARATOR);
        @chmod($file, 0666);
    }

    /**
     * Get all stored queries
     *
     * @param  string  $log  Optional suffix for the query log file name.
     * @return array<int, array{time: Carbon, query: string}>
     *
     * @throws FileNotFoundException
     */
    public static function all($log = ''): array
    {
        if (! File::exists(self::file($log))) {
            return [];
        }

        $lines = explode(self::SEPARATOR, File::get(self::file()));
        $queries = [];
        foreach ($lines as $line) {
            if (trim($line)) {
                $t = explode('|||||||', $line);
                $queries[] = [
                    'time' => Carbon::createFromTimestamp($t[0]),
                    'query' => Crypt::decryptString($t[1]),
                ];
            }
        }

        return $queries;
    }

    /**
     * Register the service
     */
    public static function register(): void
    {
        // Query logging
        /**
         * Log all queries except select
         */
        DB::listen(
            function ($query) {
                /** @var QueryExecuted $query */
                $bindings = $query->bindings;
                $normalizedBindings = array_map(
                    function ($binding) {
                        if (is_object($binding) && is_a($binding, \DateTime::class)) {
                            return '"'.$binding->format('Y-m-d H:i:s').'"';
                        }
                        if (is_object($binding) && method_exists($binding, '__toString')) {
                            /** @var string $stringValue */
                            $stringValue = $binding->__toString();

                            return '"'.$stringValue.'"';
                        }

                        return is_string($binding) ? '"'.$binding.'"' : $binding;
                    },
                    $bindings
                );
                /** @var list<string> $normalizedBindings */
                $sql = Str::replaceArray('?', $normalizedBindings, $query->sql);
                $keyword = explode(' ', $sql)[0];
                if ($keyword !== 'select') {
                    self::put($sql);
                }
            }
        );
    }

    /**
     * Clear the query log and restore cross-container write permissions.
     *
     * @return void
     */
    public static function clear()
    {
        $file = self::file();
        File::delete($file);
        File::append($file, '');
        @chmod($file, 0666);
    }

    /**
     * Get the date of the last query
     *
     * @return Carbon
     */
    public static function date()
    {
        return Carbon::createFromTimestamp((int) filemtime(self::file()));
    }
}
