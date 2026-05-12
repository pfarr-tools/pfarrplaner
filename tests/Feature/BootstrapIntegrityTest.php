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
 */

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class BootstrapIntegrityTest extends TestCase
{
    public function testWebRoutesAreLoaded()
    {
        $this->assertTrue(Route::has('login'), 'Route "login" not registered');
    }

    public function testApiRoutesAreLoaded()
    {
        $routes = Route::getRoutes();
        $apiRoutes = collect($routes->getRoutes())
            ->filter(fn($r) => str_starts_with($r->getName() ?? '', 'api.'));

        $this->assertGreaterThan(0, $apiRoutes->count(), 'No api.* routes registered');
    }

    public function testExtranetMiddlewareGroupExists()
    {
        $router = app('router');
        $groups = $router->getMiddlewareGroups();
        $this->assertArrayHasKey('extranet', $groups, 'Middleware group "extranet" is not defined');
    }

    public function testScheduledCommandsAreRegistered()
    {
        // Bootstrap the console kernel so routes/console.php is loaded and schedule is populated
        $kernel = app(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();
        $commands = collect($events)->map(fn($e) => $e->command ?? '')->toArray();

        $this->assertTrue(
            collect($commands)->contains(fn($c) => str_contains($c, 'liturgy:get')),
            'liturgy:get is not scheduled'
        );
        $this->assertTrue(
            collect($commands)->contains(fn($c) => str_contains($c, 'cache:prune-stale-tags')),
            'cache:prune-stale-tags is not scheduled'
        );
    }

    public function testCustomExceptionHandlerIsRegistered()
    {
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $this->assertInstanceOf(\App\Exceptions\Handler::class, $handler);
    }
}
