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

namespace App\Models;

use App\Traits\HasAttributeLabels;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;

/**
 * Base model for application models with shared administrative behavior.
 *
 * @phpstan-consistent-constructor
 */
class AbstractModel extends Model
{
    use HasAttributeLabels;

    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    /** @var array<string, list<string>> Resource actions omitted from each route group. */
    public static array $exceptRoutes = [];

    /** @var array<string, array<string, array{list<string>, string}>> Default HTTP methods and URL patterns by route group and action. */
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

    /** @var array<string, array<string, array{list<string>, string}>> Additional route definitions supplied by concrete models. */
    protected static $routes = [
        'web' => [],
        'api' => [],
    ];

    /** @var string Path segment used for the editor. */
    protected static $editVerb = 'bearbeiten';

    /** @var array<string, mixed> Default validation rules */
    public static array $validationRules = [];

    protected static string $prefix = '';

    protected static string $prefixPlural = '';

    protected static string $path = 'admin';

    protected $guarded = [];

    /** @var string Display title of the administrative module. */
    public static $adminTitle = '';

    /** @var string Icon identifying the administrative module. */
    public static $adminIcon = '';

    /** @var string Navigation group containing the module. */
    public static $adminGroup = '';

    protected $appends = ['label'];

    /** @var string Attribute used to resolve route model bindings. */
    protected static $modelKeyInRoute = 'id';

    /** @var list<string> Relationships eager loaded for the index. */
    public static $relationsForIndex = [];

    /** @var list<string> Relationships eager loaded for the editor. */
    public static $relationsForEditor = [];

    /**
     * Describe the authorized administrative navigation entry, or disable it.
     *
     * @return array{text: string, group: string, icon: string, url: string, active: bool, inertia: true}|false
     */
    public static function getAdminModuleConfig()
    {
        if (! static::$adminIcon) {
            return false;
        }
        if (! Auth::user() || ! Auth::user()->can('index', get_called_class())) {
            return false;
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

    /**
     * @param  string  $verb  Action verb.\n     * @return string
     */
    public static function getContractName(string $verb): string
    {
        return static::relatedClass('App\\Contracts\\###\\'.ucfirst($verb).'s#p#');
    }

    /**
     * @param  string  $verb  Action verb.\n     * @return string
     */
    public static function getActionName(string $verb): string
    {
        return static::relatedClass('App\\Actions\\###\\'.ucfirst($verb).'###');
    }

    /**
     * Resolve the container-bound action for the model contract of a verb.
     *
     * @return mixed Action instance registered for the derived contract.
     */
    public function getContractedAction(string $verb): mixed
    {
        return app(static::getContractName($verb));
    }

    /**
     * @param  string  $pattern  Related model pattern.\n     * @return string
     */
    public static function relatedClass(string $pattern): string
    {
        return static::relatedName($pattern);
    }

    /**
     * @param  string  $pattern  Related model pattern.\n     * @return string
     */
    public static function relatedName(string $pattern): string
    {
        return Str::replace(['###', '#p#'], [static::modelName(), Str::plural(static::modelName())], $pattern);
    }

    /**
     * @param  string  $pattern  Related model pattern.\n     * @return string
     */
    public static function relatedUrl(string $pattern): string
    {
        return Str::replace(['###', '#p#'], [static::$prefix, static::$prefixPlural], $pattern);
    }

    public static function modelName(): string
    {
        return Str::afterLast(get_called_class(), '\\');
    }

    /**
     * @param  string  $verb  Action verb.\n     * @return string
     */
    public static function getEventName(string $verb): string
    {
        return static::relatedClass('App\\Events\\'.ucfirst($verb).'###');
    }

    /**
     * Build the Inertia page path for a model controller view.
     *
     * @param  string  $page  Page name relative to the model view directory.
     * @return string Inertia page component path.
     */
    public static function getVuePath(string $page)
    {
        return (static::$path ? Str::ucfirst(static::$path).'/' : '')
            .Str::ucfirst(static::relatedName('###/'.Str::ucfirst($page)));
    }

    /**
     * Resolve route definitions for this model, including configured overrides.
     *
     * @return array<string, array<string, array{verb: string, httpVerbs: list<string>, urlPattern: string}>> Routes grouped by route class and name.
     */
    public static function getRoutes()
    {
        $routeClassPrefixes = ['web' => static::$path];
        $defaultRoutes = static::$defaultRoutes;
        $customRoutes = static::$routes;

        $routeClasses = array_merge(array_keys($customRoutes), array_keys($defaultRoutes));
        foreach (array_keys($defaultRoutes) as $routeClass) {
            foreach (static::$exceptRoutes[$routeClass] ?? [] as $exception) {
                if (isset(($defaultRoutes[$routeClass] ?? [])[$exception])) {
                    unset($defaultRoutes[$routeClass][$exception]);
                }
            }
        }

        $myRoutes = [];
        foreach ($routeClasses as $routeClass) {
            $myRoutes[$routeClass] = [];
            $records = array_merge(
                $customRoutes[$routeClass] ?? [],
                $defaultRoutes[$routeClass] ?? []
            );

            // edit verb exception rule:
            // if "show" is not defined, remove the "edit verb" from the "edit" url
            if (isset($records['edit']) && (! isset($records['show']))) {
                $records['edit'][1] = Str::replace('/'.static::$editVerb, '', $records['edit'][1]);
            }

            foreach ($records as $routeKey => $routeData) {
                $routeClassPrefix = $routeClassPrefixes[$routeClass] ?? $routeClass;
                $keyPrefix = $routeClassPrefix ? str_replace('/', '.', $routeClassPrefix).'.' : '';
                $fullRouteName = $keyPrefix.(Str::contains($routeData[1], '#p#') ? static::pluralKey(
                ) : static::singularKey()).'.'.$routeKey;
                $routeData[1] = '/'.($routeClassPrefix ? $routeClassPrefix.'/' : '').static::relatedUrl(
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
     *
     * @param  string  $routeClass  Class (web, api, ...)
     * @param  string  $verb  Verb (index, show, ...)
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
     *
     * @param  string  $routeClass  Class (web, api, ...)
     * @param  string  $verb  Verb (index, show, ...)
     * @return string Route name
     */
    public static function getSingleRoute(string $routeClass, string $verb): string
    {
        $routeName = static::getSingleRouteName($routeClass, $verb);

        return $routeName ? route($routeName) : '';
    }

    /**
     * Register configured routes whose controller methods exist.
     *
     * @param  string  $routeClass  Route group name.
     * @param  class-string  $controllerClass  Controller handling the routes.
     * @return void
     */
    protected static function registerDefaultRoutes($routeClass, $controllerClass)
    {
        if (! class_exists($controllerClass)) {
            return;
        }

        foreach (static::getRoutes()[$routeClass] ?? [] as $routeKey => $routeData) {
            if (method_exists($controllerClass, $routeData['verb'])) {
                Route::match($routeData['httpVerbs'], $routeData['urlPattern'], [$controllerClass, $routeData['verb']])
                    ->name($routeKey);
            }
        }
    }

    /**
     * Register the models resource routes
     */
    public static function registerRoutes(): void
    {
        Route::middleware([
            'auth',
        ])->group(function () {
            $controllerClass = static::controllerClass();
            if (class_exists($controllerClass)) {
                static::registerDefaultRoutes('web', $controllerClass);
            }
        });
    }

    /** Register the model's authenticated API routes. */
    public static function registerApiRoutes(): void
    {
        Route::middleware([
            'auth:sanctum,api',
        ])->group(function () {
            $controllerClass = static::apiControllerClass();
            if (class_exists($controllerClass)) {
                static::registerDefaultRoutes('api', $controllerClass);
            }
        });
    }

    /**
     * Bind available action contracts to their concrete action classes.
     *
     * @param  Application  $app  Application container receiving the bindings.
     * @return void
     */
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

    /** Return the web controller class name derived from this model. */
    /** @return string Controller class for this model. */
    public static function controllerClass(): string
    {
        return 'App\\Http\\Controllers\\'.static::modelName().'Controller';
    }

    /** Return the API controller class name derived from this model. */
    /** @return string API controller class for this model. */
    public static function apiControllerClass(): string
    {
        return 'App\\Http\\Controllers\\Api\\'.static::modelName().'Controller';
    }

    /**
     * Get the route key in the plural
     */
    public static function pluralKey(): string
    {
        return Pluralizer::plural(self::singularKey());
    }

    /**
     * Get the route key in the singular
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

    /** Return a display label derived from the model's name attribute. */
    public function getLabelAttribute(): string
    {
        return $this->name ?? '';
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return static::$modelKeyInRoute;
    }

    /**
     * Return attribute defaults used when preparing an unsaved editor model.
     *
     * @return array<string, mixed> Default attribute values.
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
        $model = new static;
        $data = array_merge(array_fill_keys($model->getFillable(), null), $model->fillDefaults(), ['name' => '']);
        $model->fill($data);

        return $model;
    }
}
