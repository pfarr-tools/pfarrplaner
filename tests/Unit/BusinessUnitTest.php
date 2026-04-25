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

namespace Tests\Unit;

use App\Models\Meetings\Business;
use App\Services\RoleService;
use Tests\AbstractModelUnitTest;

class BusinessUnitTest extends AbstractModelUnitTest
{
    protected $modelClass = Business::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    /** Override: Business policy allows all admins, no city sync needed */
    public function testUpdateViaApi()
    {
        $existing = $this->factory()->create();
        $this->assertTrue($this->testUser->can('update', $existing));

        $data = $this->factory()->raw();
        $apiRoute = 'api.' . ($this->modelClass)::singularKey() . '.update';
        $response = $this->actingAs($this->testUser, 'api')
            ->patchJson(route($apiRoute, $existing->id), $data);
        $response->assertStatus(200);
    }

    /** Override: Business policy allows all admins, no city sync needed */
    public function testDeleteViaApi()
    {
        $existing = $this->factory()->create();
        $this->assertTrue($this->testUser->can('delete', $existing));

        $apiRoute = 'api.' . ($this->modelClass)::singularKey() . '.destroy';
        $response = $this->actingAs($this->testUser, 'api')
            ->delete(route($apiRoute, $existing->id));
        $response->assertStatus(200);
        $this->assertEquals(0, ($this->modelClass)::count());
    }
}
