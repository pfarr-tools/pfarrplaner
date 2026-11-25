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

namespace App\Http\Controllers;

use App\Facades\Settings;
use App\HomeScreen\Tabs\HomeScreenTabFactory;
use App\Models\People\User;
use App\Services\RedirectorService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class HomeController
 */
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @param  Request  $request  Current request.
     * @param  string  $activeTab  Tab selected in the route or request.
     * @return RedirectResponse|Response Redirect to login/home screen or the rendered dashboard.
     */
    public function index(Request $request, string $activeTab = ''): RedirectResponse|Response
    {
        if (! Auth::user()) {
            return redirect()->route('login');
        }

        RedirectorService::saveCurrentRoute();
        $activeTab = $request->get('tab', $activeTab);

        $homeScreenSetting = Auth::user()->getSetting('homeScreen', 'homescreen:configurable');
        if (is_array($homeScreenSetting)) {
            $homeScreenSetting = $homeScreenSetting[0];
        }
        /** @var string $homeScreenSetting */
        if (substr($homeScreenSetting, 0, 6) == 'route:') {
            return redirect()->route(substr($homeScreenSetting, 6));
        }

        $user = Auth::user()->load(['userSettings', 'roles', 'permissions', 'pools']);
        $user->ensureDefaultSettings();
        /** @var array<string, mixed> $settings */
        $settings = Settings::all($user);
        /** @var array<string, mixed> $homeScreenConfig */
        $homeScreenConfig = $settings['homeScreenConfig'] ?? [];
        $showReplacements = $homeScreenConfig['showReplacements'] ?? false;

        $replacements = $showReplacements ? $user->currentReplacements() : [];
        $masteredPools = $showReplacements ? $user->currentlyMasteredPools() : [];

        $activeTabIndex = filter_var($activeTab, FILTER_SANITIZE_NUMBER_INT) ?: 0;
        /** @var array<string, mixed> $config */
        $config = Auth::user()->getSetting('homeScreenTabsConfig') ?? [];
        // $tab = HomeScreenTabFactory::getOne($config['tabs'][$activeTabIndex], $activeTabIndex);
        $tab = [];

        /** @var array<int|string, array<string, mixed>> $tabsConfig */
        $tabsConfig = $config['tabs'] ?? [];
        $tabTitles = HomeScreenTabFactory::getTitles($tabsConfig);

        $cities = $user->writableCities;

        return Inertia::render(
            'HomeScreen',
            compact('user', 'settings', 'activeTab', 'replacements', 'tab', 'tabTitles', 'cities', 'masteredPools')
        );
    }

    /**
     * Display application metadata and the current changelog.
     *
     * @return Response Inertia response with version, changelog and runtime information.
     */
    public function about(): Response
    {
        // get version
        $packageContents = file_get_contents(base_path('package.json'));
        abort_unless(is_string($packageContents), 500);
        /** @var array{version: string} $packageConfig */
        $packageConfig = json_decode($packageContents, true);
        $version = $packageConfig['version'];
        $modifiedAt = filemtime(base_path('package.json'));
        abort_unless($modifiedAt !== false, 500);
        $date = (new Carbon($modifiedAt))->setTimeZone('Europe/Berlin');
        $changelog = file_get_contents(base_path('CHANGELOG.md'));
        $env = App::environment();
        $phpVersion = phpversion();
        $laravelVersion = app()->version();
        $appName = config('app.name');
        $repositoryUrl = config('app.build_repository');
        $runtime = $this->runtimeInformation();

        return Inertia::render(
            'About',
            compact(
                'version',
                'date',
                'changelog',
                'env',
                'phpVersion',
                'laravelVersion',
                'appName',
                'repositoryUrl',
                'runtime'
            )
        );
    }

    /**
     * Return the configured container images and the current dependency status.
     *
     * @return array<string, mixed>
     */
    protected function runtimeInformation(): array
    {
        /** @var array{application: string, database: string, cache: string, storage: string} $images */
        $images = config('app.container_images');

        return [
            'images' => $images,
            'services' => [
                'application' => [
                    'image' => $images['application'],
                    'status' => 'aktiv',
                ],
                'database' => [
                    'image' => $images['database'],
                    'status' => $this->checkDependency(fn () => DB::connection()->getPdo()),
                ],
                'cache' => [
                    'image' => $images['cache'],
                    'status' => $this->checkDependency(fn () => Cache::store()->put(
                        'about-health-check',
                        true,
                        now()->addMinute()
                    )),
                ],
                'storage' => [
                    'image' => $images['storage'],
                    'status' => 'konfiguriert',
                ],
            ],
        ];
    }

    /**
     * Execute a dependency check without exposing its exception to the user.
     *
     * @param  callable(): mixed  $check
     */
    protected function checkDependency(callable $check): string
    {
        try {
            $check();

            return 'verbunden';
        } catch (\Throwable) {
            return 'nicht verfügbar';
        }
    }

    /**
     * Return one configured home-screen tab as JSON.
     *
     * @param  int|string  $tabIndex  Index of the configured tab.
     * @return JsonResponse JSON response containing the tab data.
     */
    public function tab(int|string $tabIndex): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        /** @var array{tabs: array<int|string, array<string, mixed>>} $config */
        $config = $user->getSetting('homeScreenTabsConfig') ?? [];
        $tab = HomeScreenTabFactory::getOne($config['tabs'][$tabIndex], $tabIndex);

        return response()->json($tab);
    }
}
