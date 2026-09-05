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

namespace App\Console\Commands\Skeleton;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;

abstract class AbstractSkeletonBuilder extends GeneratorCommand
{

    /** @var string $verb Verb (like "creates") */
    protected $verb = '';

    /** @var array $namespaceAdditions */
    protected $namespaceAdditions = [];

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\'.Str::studly(Str::plural($this->type)).'\\'
            .(count($this->namespaceAdditions) ? implode('\\', $this->namespaceAdditions) . '\\' : '')
            .str_replace(Str::studly($this->verb), '', $this->getBaseName($this->getNameInput()));
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the contract already exists'],
        ];
    }

    protected function buildReplacement($replacements, $key, $value)
    {
        $replacements['{{ ' . $key . ' }}'] = $value;
        $replacements['{{' . $key . '}}'] = $value;
        return $replacements;
    }

    protected function getBaseName($name)
    {
        return Str::singular(
            str_replace(
                Str::studly($this->verb) . ' ',
                '',
                basename(
                    str_replace(
                        '\\',
                        '/',
                        $name
                    )
                )
            )
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/'.strtolower(Str::plural($this->type)).'.'.strtolower($this->verb).'.stub');
    }



    protected function guessModel($name)
    {
        $basename = str_replace(Str::studly($this->verb), '', $this->getBaseName($name));
        $modelPath = is_dir(app_path('Models')) ? app_path('Models') : app_path();

        return new Collection(Finder::create()->files()->depth('< 2')->in($modelPath))
            ->reject(fn($file) => $file->getBasename('.php') != $basename)
            ->map(
                fn($file) => str_replace(
                    '.php',
                    '',
                    str_replace('/', '\\', str_replace($modelPath, 'App/Models', $file->getPathName()))
                )
            )
            ->sort()
            ->values()
            ->first();
    }

    protected function buildClass($name)
    {
        $model = $this->guessModel($name);
        $modelBase = $this->getBaseName($model);
        $replace = $this->buildReplacement([], 'model', $model);
        $replace = $this->buildReplacement($replace, 'modelBase', $modelBase);
        $replace = $this->buildReplacement($replace, 'modelBaseLower', Str::lower($modelBase));
        $replace = $this->buildReplacement($replace, 'modelBasePlural', Str::plural($modelBase));
        $replace = $this->buildReplacement($replace, 'modelBasePluralLower', Str::lower(Str::plural($modelBase)));
        $replace = $this->buildReplacement($replace, 'modelVarName', Str::camel($modelBase));
        $replace = $this->buildReplacement($replace, 'modelVarNamePlural', Str::camel(Str::plural($modelBase)));
        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }


    protected function possibleModels()
    {
        $modelPath = is_dir(app_path('Models')) ? app_path('Models') : app_path();
    }


}
