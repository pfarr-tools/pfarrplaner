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

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Class Controller
 * @package App\Http\Controllers
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public static function defaultRoutes($prefix = '')
    {
        $class = get_called_class();
        $className = str_replace('App\\Http\\Controllers\\', '', $class);
        $key = strtolower(str_replace('Controller', '', $className));
        $plural = Str::plural($key);

        $urlPrefix = Str::replace('routes.', '', __('routes.'.$prefix));
        $localizedKey = Str::replace('routes.', '', __('routes.'.$key));
        $localizedPlural = Str::replace('routes.', '', __('routes.'.$plural));


        // default routes
        if (method_exists($class, 'index')) {
            Route::get(($urlPrefix ? '/' . $urlPrefix : '') . '/' . $localizedPlural, $className . '@index')->name(
                ($prefix ? $prefix . '.' : '') . $key . '.index'
            );
        }
        if (method_exists($class, 'create')) {
            Route::get(($urlPrefix ? '/' . $urlPrefix : '') . '/' . $localizedKey.'/neu', $className . '@create')->name(
                ($prefix ? $prefix . '.' : '') . $key . '.create'
            );
        }
        if (method_exists($class, 'store')) {
            Route::post(($urlPrefix ? '/' . $urlPrefix : '') . '/' . $localizedPlural, $className . '@store')->name(
                ($prefix ? $prefix . '.' : '') . $key . '.store'
            );
        }
        if (method_exists($class, 'show')) {
            Route::get(($urlPrefix ? '/' . $urlPrefix : '') . '/' . $localizedKey . '/{' . $key . '}', $className . '@show')->name(
                ($prefix ? $prefix . '.' : '') . $key . '.show'
            );
        }
        if (method_exists($class, 'edit')) {
            Route::get(
                ($urlPrefix ? '/' . $urlPrefix : '') . '/' . $localizedKey . '/{' . $key . '}/edit',
                $className . '@edit'
            )->name(($prefix ? $prefix . '.' : '') . $key . '.edit');
        }
        if (method_exists($class, 'update')) {
            Route::patch(
                ($urlPrefix ? '/' . $urlPrefix : '') . '/' . $localizedKey . '/{' . $key . '}',
                $className . '@update'
            )->name(($prefix ? $prefix . '.' : '') . $key . '.update');
        }
        if (method_exists($class, 'delete')) {
            Route::delete(
                ($urlPrefix ? '/' . $urlPrefix : '') . '/' . $localizedKey . '/{' . $key . '}',
                $className . '@destroy'
            )->name(($prefix ? $prefix . '.' : '') . $key . '.destroy');
        }
    }

}
