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


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Str;

Route::match(['GET','POST'],'/csrf-cookie', fn () => response()->noContent());
Route::post('/_debug/needs-csrf', function () {
    return response()->json(['ok' => true]);
});
Route::get('/_debug/form', function () {
    return '<form method="POST" action="/_debug/needs-csrf"><button>Send</button></form>';
});

Route::get('/test-node', function () {
    return shell_exec('node -v') ?: 'node not found';
});


if (!function_exists('routeNames')) {
    function routeNames($model)
    {
        $routes = [];
        foreach (['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'] as $verb) {
            $routes[$verb] = "{$model}.{$verb}";
        }
        return $routes;
    }
}


// auto-register model routes
foreach (File::allFiles(app_path('Models')) as $file) {
    if (($file->getExtension() == 'php') && (!Str::contains($file->getPathname(), 'Abstract'))) {
        $className = substr('App\\Models\\' . Str::replace('/', '\\', $file->getRelativePathname()), 0, -4);
        if (method_exists($className, 'registerRoutes')) {
            $className::registerRoutes();
        }
    }
};

// import individual route files
foreach (glob(base_path('routes/web/*.php')) as $file) {
    Route::group([], $file);
}

// admin routes
Route::prefix('admin')->group(function () {
    foreach (glob(base_path('routes/web/admin/*.php')) as $file) {
        Route::group([], $file);
    }
});

// update trigger
Route::get('/ping/update', function (\Illuminate\Http\Request $request) {
    exit();
    \Illuminate\Support\Facades\Log::debug(
        'Update triggered via remote ping from ' . gethostbyaddr($request->ip()) . ' [' . $request->ip() . ']'
    );
    //exec ('php artisan install:updates >/storage/logs/update.log  &');
    \Illuminate\Support\Facades\Artisan::call('install:updates');
    $result = \Illuminate\Support\Facades\Artisan::output();
    \Illuminate\Support\Facades\Log::debug('Update terminated: ' . $result);
    return $result;
})->middleware('signed')->name('ping.update');

Route::get('/panic', function () {
    throw new \Exception('Whoops');
});

Route::get('/dash', function () {
    return \Inertia\Inertia::render('Dash');
});
