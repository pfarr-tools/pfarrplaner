<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 */

namespace Tests\Feature;

use App\Services\RoleConfigurationSynchronizer;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleConfigurationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_configuration_contains_all_roles_and_permissions(): void
    {
        $configuration = config('roles');

        $this->assertSame('web', $configuration['guard']);
        $this->assertArrayHasKey(RoleService::ROLE_SUPER_ADMIN, $configuration['roles']);
        $this->assertArrayHasKey(RoleService::ROLE_ADMIN, $configuration['roles']);
        $this->assertArrayHasKey(RoleService::ROLE_PASTOR, $configuration['roles']);
        $this->assertArrayHasKey(RoleService::ROLE_PREDICANT, $configuration['roles']);
        $this->assertContains('gd-kasualien-nur-statistik', $configuration['permissions']);
        $this->assertContains('rollen-bearbeiten', $configuration['permissions']);
    }

    public function test_sync_is_idempotent_and_keeps_unconfigured_records(): void
    {
        Role::query()->delete();
        Permission::query()->delete();
        Permission::create(['name' => 'legacy-permission', 'guard_name' => 'web']);
        Role::create(['name' => 'Legacy Rolle', 'guard_name' => 'web']);

        $synchronizer = app(RoleConfigurationSynchronizer::class);

        $first = $synchronizer->synchronize();
        $second = $synchronizer->synchronize();

        $this->assertGreaterThan(0, $first->created);
        $this->assertSame(0, $second->created);
        $this->assertDatabaseHas('roles', ['name' => 'Legacy Rolle']);
        $this->assertDatabaseHas('permissions', ['name' => 'legacy-permission']);
        $this->assertDatabaseHas('permissions', ['name' => 'gd-kasualien-nur-statistik']);
    }

    public function test_dry_run_does_not_change_the_database(): void
    {
        Role::query()->delete();
        Permission::query()->delete();
        $synchronizer = app(RoleConfigurationSynchronizer::class);

        $result = $synchronizer->synchronize(true);

        $this->assertGreaterThan(0, $result->created);
        $this->assertDatabaseMissing('roles', ['name' => RoleService::ROLE_ADMIN]);
        $this->assertDatabaseMissing('permissions', ['name' => 'admin']);
    }

    public function test_sync_resets_the_permission_cache(): void
    {
        $registrar = \Mockery::mock(PermissionRegistrar::class);
        $registrar->shouldReceive('forgetCachedPermissions')->once();

        (new RoleConfigurationSynchronizer($registrar))->synchronize();
    }

    public function test_sync_command_supports_dry_run(): void
    {
        Role::query()->delete();
        Permission::query()->delete();

        $this->artisan('roles:sync', ['--dry-run' => true])
            ->expectsOutput('Vorschau: Rollen und Berechtigungen abgeglichen.')
            ->expectsOutputToContain('Rolle: Administrator:in')
            ->assertExitCode(0);
    }

    public function test_migrations_reset_the_permission_cache(): void
    {
        $registrar = \Mockery::mock(PermissionRegistrar::class);
        $registrar->shouldReceive('forgetCachedPermissions')->once();
        $this->app->instance(PermissionRegistrar::class, $registrar);

        Event::dispatch(new MigrationsEnded('up'));
    }

    public function test_role_ui_routes_are_not_registered(): void
    {
        $this->assertFalse(Route::has('roles.index'));
        $this->assertFalse(Route::has('role.create'));
        $this->assertFalse(Route::has('role.edit'));
    }
}
