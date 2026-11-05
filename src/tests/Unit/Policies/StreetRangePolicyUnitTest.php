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

namespace Tests\Unit\Policies;

use App\Models\Parish;
use App\Models\People\User;
use App\Models\Places\StreetRange;
use App\Policies\StreetRangePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StreetRangePolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private StreetRangePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new StreetRangePolicy;
        Permission::findOrCreate('pfarramt-bearbeiten', 'web');
    }

    public function test_regular_user_cannot_create_street_range(): void
    {
        $user = User::factory()->create();
        $parish = Parish::factory()->create();
        $this->assertFalse($this->policy->create($user, $parish));
    }

    public function test_user_with_parish_permission_can_create_street_range(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('pfarramt-bearbeiten');
        $parish = Parish::factory()->create();
        $this->assertTrue($this->policy->create($user, $parish));
    }

    public function test_regular_user_cannot_update_street_range(): void
    {
        $user = User::factory()->create();
        $streetRange = StreetRange::factory()->create();
        $this->assertFalse($this->policy->update($user, $streetRange));
    }

    public function test_user_with_parish_permission_can_update_street_range(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('pfarramt-bearbeiten');
        $streetRange = StreetRange::factory()->create();
        $this->assertTrue($this->policy->update($user, $streetRange));
    }

    public function test_regular_user_cannot_delete_street_range(): void
    {
        $user = User::factory()->create();
        $streetRange = StreetRange::factory()->create();
        $this->assertFalse($this->policy->delete($user, $streetRange));
    }

    public function test_user_with_parish_permission_can_delete_street_range(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('pfarramt-bearbeiten');
        $streetRange = StreetRange::factory()->create();
        $this->assertTrue($this->policy->delete($user, $streetRange));
    }
}
