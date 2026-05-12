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

use App\Models\People\Team;
use App\Models\People\User;
use App\Models\Places\City;
use App\Policies\TeamPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private TeamPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TeamPolicy();
    }

    public function testUserWithoutCitiesCannotIndex(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->index($user));
    }

    public function testUserWithoutCitiesCannotCreate(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user));
    }

    public function testUserWithWritableCityCanIndex(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $user->cities()->attach($city->id, ['permission' => 'w']);
        $this->assertTrue($this->policy->index($user));
    }

    public function testUserWithWritableCityCanCreate(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $user->cities()->attach($city->id, ['permission' => 'w']);
        $this->assertTrue($this->policy->create($user));
    }

    public function testUserCannotUpdateTeamFromOtherCity(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $team = Team::factory()->create(['city_id' => $city->id]);
        $this->assertFalse($this->policy->update($user, $team));
    }

    public function testUserCannotDeleteTeamFromOtherCity(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $team = Team::factory()->create(['city_id' => $city->id]);
        $this->assertFalse($this->policy->delete($user, $team));
    }

    public function testUserWithWritableCityCanUpdateOwnTeam(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $user->cities()->attach($city->id, ['permission' => 'w']);
        $team = Team::factory()->create(['city_id' => $city->id]);
        $this->assertTrue($this->policy->update($user, $team));
    }
}
