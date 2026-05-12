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

namespace Tests;

use App\Models\People\User;
use App\Services\RoleService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Spatie\Permission\PermissionRegistrar;

abstract class AbstractPageLoadTest extends DuskTestCase
{
    use DatabaseTruncation;

    protected string $seeder = RoleSeeder::class;
    protected User $superAdminUser;

    /**
     * Include seeding in the initial migrate:fresh so roles exist on first run.
     */
    protected function migrateFreshUsing(): array
    {
        return [
            '--drop-views' => $this->shouldDropViews(),
            '--drop-types' => $this->shouldDropTypes(),
            '--seed'       => true,
            '--seeder'     => RoleSeeder::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->superAdminUser = User::factory()->create();
        $this->superAdminUser->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    /**
     * Assert that a page loads and optionally contains a visible heading.
     *
     * @param Browser $browser
     * @param string $url
     * @param string|null $expectedHeading
     */
    protected function assertPageLoads(
        Browser $browser,
        string $url,
        ?string $expectedHeading = null
    ): void {
        $browser->loginAs($this->superAdminUser, 'web')
                ->visit($url)
                ->waitFor('#app', 10)
                ->assertDontSee('500')
                ->assertDontSee('Whoops');

        if ($expectedHeading !== null) {
            $browser->assertSee($expectedHeading);
        }
    }
}
