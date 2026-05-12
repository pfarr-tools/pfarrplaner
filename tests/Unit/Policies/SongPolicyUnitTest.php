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

use App\Models\Liturgy\Song;
use App\Models\People\User;
use App\Policies\SongPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SongPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private SongPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SongPolicy();
    }

    public function testViewAnyAlwaysReturnsTrue(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function testViewAlwaysReturnsTrue(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->create();
        $this->assertTrue($this->policy->view($user, $song));
    }

    public function testCreateAlwaysReturnsTrue(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($this->policy->create($user));
    }

    public function testUpdateAlwaysReturnsTrue(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->create();
        $this->assertTrue($this->policy->update($user, $song));
    }

    public function testDeleteAlwaysReturnsTrue(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->create();
        $this->assertTrue($this->policy->delete($user, $song));
    }

    public function testRestoreAlwaysReturnsTrue(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->create();
        $this->assertTrue($this->policy->restore($user, $song));
    }

    public function testForceDeleteAlwaysReturnsTrue(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->create();
        $this->assertTrue($this->policy->forceDelete($user, $song));
    }
}
