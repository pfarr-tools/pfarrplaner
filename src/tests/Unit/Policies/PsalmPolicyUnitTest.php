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

use App\Models\Liturgy\Psalm;
use App\Models\People\User;
use App\Policies\PsalmPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsalmPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private PsalmPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PsalmPolicy;
    }

    public function test_view_any_always_returns_true(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_view_always_returns_true(): void
    {
        $user = User::factory()->create();
        $psalm = Psalm::factory()->create();
        $this->assertTrue($this->policy->view($user, $psalm));
    }

    public function test_create_always_returns_true(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($this->policy->create($user));
    }

    public function test_update_always_returns_true(): void
    {
        $user = User::factory()->create();
        $psalm = Psalm::factory()->create();
        $this->assertTrue($this->policy->update($user, $psalm));
    }

    public function test_delete_always_returns_true(): void
    {
        $user = User::factory()->create();
        $psalm = Psalm::factory()->create();
        $this->assertTrue($this->policy->delete($user, $psalm));
    }

    public function test_restore_always_returns_true(): void
    {
        $user = User::factory()->create();
        $psalm = Psalm::factory()->create();
        $this->assertTrue($this->policy->restore($user, $psalm));
    }

    public function test_force_delete_always_returns_true(): void
    {
        $user = User::factory()->create();
        $psalm = Psalm::factory()->create();
        $this->assertTrue($this->policy->forceDelete($user, $psalm));
    }
}
