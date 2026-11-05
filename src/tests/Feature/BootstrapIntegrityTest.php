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
 */

namespace Tests\Feature;

use App\Exceptions\Handler;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use ReflectionClass;
use Tests\TestCase;

class BootstrapIntegrityTest extends TestCase
{
    public function test_web_routes_are_loaded()
    {
        $this->assertTrue(Route::has('login'), 'Route "login" not registered');
    }

    public function test_api_routes_are_loaded()
    {
        $routes = Route::getRoutes();
        $apiRoutes = collect($routes->getRoutes())
            ->filter(fn ($r) => str_starts_with($r->getName() ?? '', 'api.'));

        $this->assertGreaterThan(0, $apiRoutes->count(), 'No api.* routes registered');
    }

    public function test_extranet_middleware_group_exists()
    {
        $router = app('router');
        $groups = $router->getMiddlewareGroups();
        $this->assertArrayHasKey('extranet', $groups, 'Middleware group "extranet" is not defined');
    }

    public function test_scheduled_commands_are_registered()
    {
        // Bootstrap the console kernel so routes/console.php is loaded and schedule is populated
        $kernel = app(Kernel::class);
        $kernel->bootstrap();

        $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();
        $commands = collect($events)->map(fn ($e) => $e->command ?? '')->toArray();

        $this->assertTrue(
            collect($commands)->contains(fn ($c) => str_contains($c, 'liturgy:get')),
            'liturgy:get is not scheduled'
        );
        $this->assertTrue(
            collect($commands)->contains(fn ($c) => str_contains($c, 'cache:prune-stale-tags')),
            'cache:prune-stale-tags is not scheduled'
        );
    }

    public function test_integration_commands_are_registered()
    {
        $registeredCommands = array_keys(Artisan::all());
        $integrationCommands = collect(glob(app_path('Integrations/*/Commands/*.php')) ?: [])
            ->map(function (string $path) {
                $relativePath = str_replace([app_path().'/', '.php', '/'], ['', '', '\\'], $path);
                $class = 'App\\'.$relativePath;
                $reflection = new ReflectionClass($class);
                $signature = $reflection->getDefaultProperties()['signature'] ?? '';

                return strtok($signature, ' ');
            })
            ->filter()
            ->values();

        $this->assertNotEmpty($integrationCommands, 'No integration commands found');

        foreach ($integrationCommands as $commandName) {
            $this->assertContains($commandName, $registeredCommands, "Integration command \"{$commandName}\" is not registered");
        }
    }

    public function test_custom_exception_handler_is_registered()
    {
        $handler = app(ExceptionHandler::class);
        $this->assertInstanceOf(Handler::class, $handler);
    }
}
