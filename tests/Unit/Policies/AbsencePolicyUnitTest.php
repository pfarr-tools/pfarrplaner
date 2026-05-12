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

namespace Tests\Unit\Policies;

use App\Models\Leave\Absence;
use App\Models\People\User;
use App\Policies\AbsencePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AbsencePolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private AbsencePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AbsencePolicy();
        // Create permissions so hasPermissionTo() does not throw
        Permission::findOrCreate('fremden-urlaub-bearbeiten', 'web');
    }

    public function testOwnerCanViewOwnAbsence(): void
    {
        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);
        $this->assertTrue($this->policy->view($user, $absence));
    }

    public function testOwnerCanUpdateOwnAbsence(): void
    {
        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);
        $this->assertTrue($this->policy->update($user, $absence));
    }

    public function testOwnerCanDeleteOwnAbsence(): void
    {
        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);
        $this->assertTrue($this->policy->delete($user, $absence));
    }

    public function testStrangerCannotUpdateOthersAbsence(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $owner->id]);
        $this->assertFalse($this->policy->update($stranger, $absence));
    }

    public function testManagerWithFlagCanCreateAbsence(): void
    {
        $user = User::factory()->create(['manage_absences' => true]);
        $this->assertTrue($this->policy->create($user));
    }

    public function testRegularUserCannotCreateAbsenceForOthers(): void
    {
        $user = User::factory()->create(['manage_absences' => false]);
        $this->assertFalse($this->policy->create($user));
    }

    public function testSelfAdministerReturnsTrueWhenNoAdminsAssigned(): void
    {
        $user = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $user->id]);
        $this->assertTrue($this->policy->selfAdminister($user, $absence));
    }

    public function testSelfAdministerReturnsFalseForOthersAbsence(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $absence = Absence::factory()->create(['user_id' => $owner->id]);
        $this->assertFalse($this->policy->selfAdminister($other, $absence));
    }
}
