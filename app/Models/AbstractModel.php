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

namespace App\Models;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Static_;

class AbstractModel extends Model
{

    use HasFactory;

    public static array $exceptRoutes = [];
    protected static $defaultRoutes = [
        'web' => [
            'index' => [['GET', 'HEAD'], '#p#'],
            'create' => [['GET', 'HEAD'], '#p#/neu'],
            'show' => [['GET', 'HEAD'], '###/{modelId}'],
            'edit' => [['GET', 'HEAD'], '###/{modelId}/bearbeiten'],
            'store' => [['POST'], '#p#'],
            'update' => [['PATCH', 'PUT'], '###/{modelId}'],
            'destroy' => [['DELETE'], '###/{modelId}'],
        ],
        'api' => [
            'index' => [['GET', 'HEAD'], '#p#'],
            'show' => [['GET', 'HEAD'], '###/{modelId}'],
            'store' => [['POST'], '#p#'],
            'update' => [['PATCH', 'PUT'], '###/{modelId}'],
            'destroy' => [['DELETE'], '###/{modelId}'],
        ],
    ];
    protected static $routes = [
        'web' => [],
        'api' => [],
    ];
    protected static $editVerb = 'bearbeiten';

    /** @var array[] $validationRules Default validation rules */
    public static array $validationRules = [];
    protected static string $prefix = '';
    protected static string $prefixPlural = '';
    protected static string $path = 'admin';
    protected $guarded = [];

    public static $adminTitle = '';
    public static $adminIcon = '';
    public static $adminGroup = '';

    protected $appends = ['label'];

    protected static $modelKeyInRoute = 'id';

    public static $relationsForIndex = [];
    public static $relationsForEditor = [];

    /**
     * @return array|bool
     */
    public static function getAdminModuleConfig()
    {
        if (!static::$adminIcon) {
            false;
        }
        if (!Auth::user() || !Auth::user()->can('index', get_called_class())) {
            false;
        }
        return [
            'text' => static::$adminTitle ?: ucfirst(static::$prefixPlural),
            'group' => static::$adminGroup,
            'icon' => static::$adminIcon,
            'url' => static::getSingleRoute('web', 'index'),
            'active' => Route::currentRouteName() == static::getSingleRouteName('web', 'index'),
            'inertia' => true,
        ];
    }


    public static function getContractName(string $verb): string
    {
        return static::relatedClass('App\\Contracts\\###\\' . ucfirst($verb) . 's#p#');
    }

    public static function getActionName(string $verb): string
    {
        return static::relatedClass('App\\Actions\\###\\' . ucfirst($verb) . '###');
    }

    public function getContractedAction(string $verb)
    {
        return app(static::getContractName($verb));
    }

    public static function relatedClass(string $pattern): string
    {
        return static::relatedName($pattern);
    }

    public static function relatedName(string $pattern): string
    {
        return Str::replace(['###', '#p#'], [static::modelName(), Str::plural(static::modelName())], $pattern);
    }

    public static function relatedUrl(string $pattern): string
    {
        return Str::replace(['###', '#p#'], [static::$prefix, static::$prefixPlural], $pattern);
    }

    public static function modelName(): string
    {
        return Str::afterLast(get_called_class(), '\\');
    }

    public static function getEventName(string $verb): string
    {
        return static::relatedClass('App\\Events\\' . ucfirst($verb) . '###');
    }

    public static function getVuePath(string $page)
    {
        return (static::$path ? Str::ucfirst(static::$path) . '/' : '')
            . Str::ucfirst(static::relatedName('###/' . Str::ucfirst($page)));
    }


    public static function getRoutes()
    {
        $routeClassPrefixes = ['web' => static::$path];

        $routeClasses = array_merge(array_keys(static::$routes ?? []), array_keys(static::$defaultRoutes ?? []));
        foreach (array_keys(static::$defaultRoutes ?? []) as $routeClass) {
            foreach ((static::$exceptRoutes ?? [])[$routeClass] ?? [] as $exception) {
                if (isset((static::$defaultRoutes[$routeClass] ?? [])[$exception])) {
                    unset (static::$defaultRoutes[$routeClass][$exception]);
                }
            }
        }

        $myRoutes = [];
        foreach ($routeClasses as $routeClass) {
            $myRoutes[$routeClass] = [];
            $records = array_merge(
                (static::$routes ?? [])[$routeClass] ?? [],
                (static::$defaultRoutes ?? [])[$routeClass] ?? []
            );

            // edit verb exception rule:
            // if "show" is not defined, remove the "edit verb" from the "edit" url
            if (isset($records['edit']) && (!isset($records['show']))) {
                $records['edit'][1] = Str::replace('/' . static::$editVerb, '', $records['edit'][1]);
            }

            foreach ($records as $routeKey => $routeData) {
                $routeClassPrefix = $routeClassPrefixes[$routeClass] ?? $routeClass;
                $keyPrefix = $routeClassPrefix ? str_replace('/', '.', $routeClassPrefix) . '.' : '';
                $fullRouteName = $keyPrefix . (Str::contains($routeData[1], '#p#') ? static::pluralKey(
                    ) : static::singularKey()) . '.' . $routeKey;
                $routeData[1] = '/' . ($routeClassPrefix ? $routeClassPrefix . '/' : '') . static::relatedUrl(
                        $routeData[1]
                    );
                $myRoutes[$routeClass][$fullRouteName] = [
                    'verb' => $routeKey,
                    'httpVerbs' => $routeData[0],
                    'urlPattern' => $routeData[1],
                ];
            }
        }
        return $myRoutes;
    }

    /**
     * Get a single route name for this model
     * @param string $routeClass Class (web, api, ...)
     * @param string $verb Verb (index, show, ...)
     * @return string Route name
     */
    public static function getSingleRouteName(string $routeClass, string $verb): string
    {
        foreach (static::getRoutes()[$routeClass] ?? [] as $routeKey => $routeData) {
            if ($routeData['verb'] == $verb) {
                return $routeKey;
            }
        }
        return '';
    }

    /**
     * Get a single route name for this model
     * @param string $routeClass Class (web, api, ...)
     * @param string $verb Verb (index, show, ...)
     * @return string Route name
     */
    public static function getSingleRoute(string $routeClass, string $verb): string
    {
        $routeName = static::getSingleRouteName($routeClass, $verb);
        return $routeName ? route($routeName) : '';
    }

    protected static function registerDefaultRoutes($routeClass, $controllerClass)
    {
        foreach (static::getRoutes()[$routeClass] ?? [] as $routeKey => $routeData) {
            if (method_exists($controllerClass, $routeData['verb'])) {
                Route::match($routeData['httpVerbs'], $routeData['urlPattern'], [$controllerClass, $routeData['verb']])
                    ->name($routeKey);
            }
        }
    }

    /**
     * Register the models resource routes
     *
     * @return void
     */
    public static function registerRoutes(): void
    {
        Route::middleware([
                              'auth',
                          ])->group(function () {
            static::registerDefaultRoutes('web', static::controllerClass());
        });
    }

    public static function registerApiRoutes(): void
    {
        Route::middleware([
                              'auth:api',
                          ])->group(function () {
            static::registerDefaultRoutes('api', static::apiControllerClass());
        });
    }

    public static function registerContracts(Application $app)
    {
        foreach (['create', 'update', 'delete'] as $verb) {
            $contract = static::getContractName($verb);
            $action = static::getActionName($verb);
            if (interface_exists($contract) && class_exists($action)) {
                $app->bind($contract, $action);
            }
        }
    }

    /**
     * @return string
     */
    public static function controllerClass(): string
    {
        return 'App\\Http\\Controllers\\' . static::modelName() . 'Controller';
    }

    public static function apiControllerClass(): string
    {
        return 'App\\Http\\Controllers\\Api\\' . static::modelName() . 'Controller';
    }

    /**
     * Get the route key in the plural
     *
     * @return string
     */
    public static function pluralKey(): string
    {
        return Pluralizer::plural(self::singularKey());
    }

    /**
     * Get the route key in the singular
     *
     * @return string
     */
    public static function singularKey(): string
    {
        return strtolower(static::modelName());
    }

    /**
     * Get the prop name for the model
     */
    public static function propKey(): string
    {
        return Str::camel(static::modelName());
    }

    /**
     * @return string
     */
    public function getLabelAttribute(): string
    {
        return $this->name;
    }


    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return static::$modelKeyInRoute;
    }

    /**
     * fill defaults array
     */
    public function fillDefaults(): array
    {
        return [];
    }


    /**
     * Get an empty model instance
     */
    public static function getEmptyModel(): AbstractModel
    {
        $model = new static();
        $data = array_merge(array_fill_keys($model->getFillable(), null), $model->fillDefaults(), ['name' => '']);
        $model->fill($data);
        return $model;
    }
}
