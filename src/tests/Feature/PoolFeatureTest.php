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

    public function test_groupable_flag_is_persisted(): void
    {
        $pool = Pool::factory()->create(['groupable' => true]);

        $this->assertTrue($pool->fresh()->groupable);
    }
}
