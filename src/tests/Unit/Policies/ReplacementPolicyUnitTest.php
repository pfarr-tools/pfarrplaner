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
use App\Models\Leave\Replacement;
use App\Models\People\User;
use App\Policies\ReplacementPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReplacementPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private ReplacementPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ReplacementPolicy();
        Permission::findOrCreate('fremden-urlaub-bearbeiten', 'web');
    }

    public function testAbsenceOwnerCanCreateReplacement(): void
    {
        $absence = Absence::factory()->create();
        $this->assertTrue($this->policy->create($absence->user, $absence));
    }

    public function testUnrelatedUserCannotCreateReplacement(): void
    {
        $user = User::factory()->create();
        $absence = Absence::factory()->create();
        $this->assertFalse($this->policy->create($user, $absence));
    }

    public function testAbsenceOwnerCanUpdateReplacement(): void
    {
        $replacement = Replacement::factory()->create();
        $this->assertTrue($this->policy->update($replacement->absence->user, $replacement));
    }

    public function testUnrelatedUserCannotUpdateReplacement(): void
    {
        $user = User::factory()->create();
        $replacement = Replacement::factory()->create();
        $this->assertFalse($this->policy->update($user, $replacement));
    }

    public function testAbsenceOwnerCanDeleteReplacement(): void
    {
        $replacement = Replacement::factory()->create();
        $this->assertTrue($this->policy->delete($replacement->absence->user, $replacement));
    }
}
