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

use App\Models\Leave\Pool;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\AbstractModelFeatureTest;

class PoolFeatureTest extends AbstractModelFeatureTest
{
    use RefreshDatabase;

    protected $modelClass = Pool::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    /**
     * The pool editor must not depend on the removed legacy users.name column.
     */
    public function test_editor_loads_without_legacy_user_name_column(): void
    {
        $pool = Pool::factory()->create();

        $this->actingAs($this->testUser)
            ->get(route(Pool::getSingleRouteName('web', 'edit'), $pool->id))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component(Pool::getVuePath('editor'))
                ->has('people')
                ->etc()
            );
    }

    public function test_groupable_flag_is_persisted(): void
    {
        $pool = Pool::factory()->create(['groupable' => true]);

        $this->assertTrue($pool->fresh()->groupable);
    }
}
