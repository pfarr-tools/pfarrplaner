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
        $this->policy = new ReplacementPolicy;
        Permission::findOrCreate('fremden-urlaub-bearbeiten', 'web');
    }

    public function test_absence_owner_can_create_replacement(): void
    {
        $absence = Absence::factory()->create();
        $this->assertTrue($this->policy->create($absence->user, $absence));
    }

    public function test_unrelated_user_cannot_create_replacement(): void
    {
        $user = User::factory()->create();
        $absence = Absence::factory()->create();
        $this->assertFalse($this->policy->create($user, $absence));
    }

    public function test_absence_owner_can_update_replacement(): void
    {
        $replacement = Replacement::factory()->create();
        $this->assertTrue($this->policy->update($replacement->absence->user, $replacement));
    }

    public function test_unrelated_user_cannot_update_replacement(): void
    {
        $user = User::factory()->create();
        $replacement = Replacement::factory()->create();
        $this->assertFalse($this->policy->update($user, $replacement));
    }

    public function test_absence_owner_can_delete_replacement(): void
    {
        $replacement = Replacement::factory()->create();
        $this->assertTrue($this->policy->delete($replacement->absence->user, $replacement));
    }
}
