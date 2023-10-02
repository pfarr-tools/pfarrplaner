<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
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

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UpdateService
{

    protected $cachedJson = [];

    public function __construct()
    {
        $this->cachedJson['package'] = $d = $this->getJson('package.json');
        $this->cachedJson['composer'] = $c = $this->getJson('composer.json');
    }


    /**
     * Read the specified file
     * @param $jsonFile Path to Json file
     * @return array|mixed
     */
    protected function getJson($jsonFile)
    {
        return json_decode(file_get_contents($jsonFile), true) ?? [];
    }

    /**
     * Check if the only change in a json object is the version string
     * @param array $json JSON data
     * @param string $cacheKey Cache key
     * @return bool
     */
    protected function checkIfJsonHasOnlyVersionUpdate($json, $cacheKey)
    {
        $diff = array_keys($this->arrayRecursiveDiff($json, $this->cachedJson[$cacheKey])) ?? [];
        return (count($diff) == 1) && ($diff[0] == 'version');
    }

    /**
     * Compare two arrays recursively and return difference
     * @param $aArray1 First array
     * @param $aArray2 Second array
     * @return array
     */
    protected function arrayRecursiveDiff($aArray1, $aArray2)
    {
        $aReturn = array();

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) {
                        $aReturn[$mKey] = $aRecursiveDiff;
                    }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }
        return $aReturn;
    }

    /**
     * Return current commit ID
     * @return string
     */
    public function currentCommit(): ?string
    {
        return shell_exec('git rev-parse HEAD');
    }

    /**
     * Get all files that would be affected by pulling an update
     * @return Collection
     */
    public function getUpdateableFiles(): Collection
    {
        exec('git fetch');
        $log = shell_exec('git log --name-status origin/main');

        $log = explode("\n", substr($log, 0, strpos($log, $this->currentCommit())));
        $files = collect();

        foreach ($log as $line) {
            if (str_contains($line, "\t")) {
                $files->push(Str::afterLast($line, "\t"));
            }
        }

        return $files;
    }

    /**
     * Check if an update is available
     * @param Collection|null $files optional file collection to check against
     * @return bool
     */
    public function hasUpdate(Collection $files = null): bool
    {
        $files ??= $this->getUpdateableFiles();
        return $files->count() > 0;
    }

    /**
     * Check which actions would be required by an update
     * @param Collection|null $files optional file collection to check against
     * @return array Actions
     */
    public function getUpdateActions(Collection $files = null): array
    {
        $files ??= $this->getUpdateableFiles();
        $actions = [];

        if ($this->hasFileChanges('composer.json', $files) && (!$this->checkIfJsonHasOnlyVersionUpdate(
                $this->getJson('composer.json'),
                'composer'
            ))) {
            $actions['composer'] = 'Composer updates';
        }
        if ($this->hasFileChanges('package.json', $files) && (!$this->checkIfJsonHasOnlyVersionUpdate(
                $this->getJson('package.json'),
                'package'
            ))) {
            $actions['npm'] = 'NPM package installs';
        }
        $actions['browserslist'] = 'Browserlist update';
        if ($this->hasFileChanges('resources/js/', $files) || isset($actions['npm'])) {
            $actions['webpack'] = 'Webpack (compiling resources)';
        }
        if ($this->hasFileChanges('resources/views/', $files)) {
            $actions['view-cache'] = 'Clear view cache';
        }
        if ($this->hasFileChanges('database/migrations/', $files)) {
            $actions['migrations'] = 'Database migrations';
        }
        $actions['optimizations'] = 'Cache optimizations';
        $actions['queue'] = 'Restart queue workers';
        return $actions;
    }

    /**
     * Check if an update has changes to a specific path
     * @param String $path (beginning of) path
     * @param Collection|null $files optional file collection to check against
     * @return bool
     */
    public function hasFileChanges($path, Collection $files = null): bool
    {
        return $files->filter(function ($item) use ($path) {
                return Str::startsWith($item, $path);
            })->count() > 0;
    }


}
