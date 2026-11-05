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
        $this->policy = new TeamPolicy;
    }

    public function test_user_without_cities_cannot_index(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->index($user));
    }

    public function test_user_without_cities_cannot_create(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user));
    }

    public function test_user_with_writable_city_can_index(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $user->cities()->attach($city->id, ['permission' => 'w']);
        $this->assertTrue($this->policy->index($user));
    }

    public function test_user_with_writable_city_can_create(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $user->cities()->attach($city->id, ['permission' => 'w']);
        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_cannot_update_team_from_other_city(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $team = Team::factory()->create(['city_id' => $city->id]);
        $this->assertFalse($this->policy->update($user, $team));
    }

    public function test_user_cannot_delete_team_from_other_city(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $team = Team::factory()->create(['city_id' => $city->id]);
        $this->assertFalse($this->policy->delete($user, $team));
    }

    public function test_user_with_writable_city_can_update_own_team(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $user->cities()->attach($city->id, ['permission' => 'w']);
        $team = Team::factory()->create(['city_id' => $city->id]);
        $this->assertTrue($this->policy->update($user, $team));
    }
}
