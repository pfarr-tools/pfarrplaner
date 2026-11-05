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

use App\Models\Leave\Absence;
use App\Models\Leave\Pool;
use App\Models\Leave\Replacement;
use App\Models\Parish;
use App\Models\People\Participant;
use App\Models\People\Team;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMergeFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $source;

    protected User $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = User::factory()->create();
        $this->target = User::factory()->create();
    }

    /**
     * @return void
     */
    public function test_merge_transfers_participants()
    {
        $city = City::factory()->create();
        $service = Service::factory()->create(['city_id' => $city->id]);
        $this->source->services()->attach($service->id, ['category' => 'P']);

        $this->source->mergeInto($this->target);

        $this->assertEquals(1, Participant::where('user_id', $this->target->id)->count());
        $this->assertEquals(0, Participant::where('user_id', $this->source->id)->count());
    }

    /**
     * @return void
     */
    public function test_merge_transfers_absences()
    {
        Absence::create(['user_id' => $this->source->id, 'from' => now(), 'to' => now()->addDay(), 'reason' => 'Urlaub']);

        $this->source->mergeInto($this->target);

        $this->assertEquals(1, Absence::where('user_id', $this->target->id)->count());
        $this->assertEquals(0, Absence::where('user_id', $this->source->id)->count());
    }

    /**
     * @return void
     */
    public function test_merge_transfers_user_settings()
    {
        UserSetting::create(['user_id' => $this->source->id, 'key' => 'test_key', 'value' => 'test_value']);

        $this->source->mergeInto($this->target);

        $this->assertEquals(1, UserSetting::where('user_id', $this->target->id)->where('key', 'test_key')->count());
        $this->assertEquals(0, UserSetting::where('user_id', $this->source->id)->count());
    }

    /**
     * City associations from both users are kept (additive union).
     *
     * @return void
     */
    public function test_merge_combines_cities()
    {
        $cityA = City::factory()->create();
        $cityB = City::factory()->create();
        $this->source->cities()->attach($cityA->id, ['permission' => 'w']);
        $this->target->cities()->attach($cityB->id, ['permission' => 'r']);

        $this->source->mergeInto($this->target);

        $this->target->refresh();
        $cityIds = $this->target->cities()->pluck('cities.id');
        $this->assertContains($cityA->id, $cityIds);
        $this->assertContains($cityB->id, $cityIds);
    }

    /**
     * When both users share a city, the better permission wins.
     *
     * @return void
     */
    public function test_merge_cities_keeps_better_permission()
    {
        $city = City::factory()->create();
        $this->source->cities()->attach($city->id, ['permission' => 'a']);
        $this->target->cities()->attach($city->id, ['permission' => 'r']);

        $this->source->mergeInto($this->target);

        $this->target->refresh();
        $permission = $this->target->cities()->where('cities.id', $city->id)->first()->pivot->permission;
        $this->assertEquals('a', $permission);
    }

    /**
     * Target retains its better permission when source has a weaker one.
     *
     * @return void
     */
    public function test_merge_cities_does_not_downgrade_permission()
    {
        $city = City::factory()->create();
        $this->source->cities()->attach($city->id, ['permission' => 'r']);
        $this->target->cities()->attach($city->id, ['permission' => 'a']);

        $this->source->mergeInto($this->target);

        $this->target->refresh();
        $permission = $this->target->cities()->where('cities.id', $city->id)->first()->pivot->permission;
        $this->assertEquals('a', $permission);
    }

    /**
     * City scopes (person visibility) are merged additively.
     *
     * @return void
     */
    public function test_merge_combines_city_scopes()
    {
        $cityA = City::factory()->create();
        $cityB = City::factory()->create();
        $this->source->cityScopes()->attach($cityA->id);
        $this->target->cityScopes()->attach($cityB->id);

        $this->source->mergeInto($this->target);

        $this->target->refresh();
        $scopeIds = $this->target->cityScopes()->pluck('cities.id');
        $this->assertContains($cityA->id, $scopeIds);
        $this->assertContains($cityB->id, $scopeIds);
    }

    /**
     * @return void
     */
    public function test_merge_transfers_parishes()
    {
        $parish = Parish::factory()->create();
        $this->source->parishes()->attach($parish->id);

        $this->source->mergeInto($this->target);

        $this->assertTrue($this->target->parishes()->where('parishes.id', $parish->id)->exists());
    }

    /**
     * When both users are already in the same parish, the target must appear exactly once after merge.
     *
     * @return void
     */
    public function test_merge_parish_no_duplicate_when_both_already_members()
    {
        $parish = Parish::factory()->create();
        $this->source->parishes()->attach($parish->id);
        $this->target->parishes()->attach($parish->id);

        $this->source->mergeInto($this->target);

        $this->assertEquals(1, $this->target->parishes()->where('parishes.id', $parish->id)->count());
    }

    /**
     * @return void
     */
    public function test_merge_transfers_teams()
    {
        $team = Team::factory()->create();
        $this->source->teams()->attach($team->id);

        $this->source->mergeInto($this->target);

        $this->assertTrue($this->target->teams()->where('teams.id', $team->id)->exists());
    }

    /**
     * When both users are already in the same team, the target must appear exactly once after merge.
     *
     * @return void
     */
    public function test_merge_team_no_duplicate_when_both_already_members()
    {
        $team = Team::factory()->create();
        $this->source->teams()->attach($team->id);
        $this->target->teams()->attach($team->id);

        $this->source->mergeInto($this->target);

        $this->assertEquals(1, $this->target->teams()->where('teams.id', $team->id)->count());
    }

    /**
     * @return void
     */
    public function test_merge_combines_home_cities()
    {
        $cityA = City::factory()->create();
        $cityB = City::factory()->create();
        $this->source->homeCities()->attach($cityA->id);
        $this->target->homeCities()->attach($cityB->id);

        $this->source->mergeInto($this->target);

        $this->target->refresh();
        $homeCityIds = $this->target->homeCities()->pluck('cities.id');
        $this->assertContains($cityA->id, $homeCityIds);
        $this->assertContains($cityB->id, $homeCityIds);
    }

    /**
     * @return void
     */
    public function test_merge_combines_pools()
    {
        $poolA = Pool::factory()->create();
        $poolB = Pool::factory()->create();
        $this->source->pools()->attach($poolA->id);
        $this->target->pools()->attach($poolB->id);

        $this->source->mergeInto($this->target);

        $this->target->refresh();
        $poolIds = $this->target->pools()->pluck('pools.id');
        $this->assertContains($poolA->id, $poolIds);
        $this->assertContains($poolB->id, $poolIds);
    }

    /**
     * Replacement pivot entries (replacement person) are moved from source to target.
     *
     * @return void
     */
    public function test_merge_transfers_replacements()
    {
        $absence = Absence::create(['user_id' => $this->target->id, 'from' => now(), 'to' => now()->addDay(), 'reason' => 'Urlaub']);
        $replacement = Replacement::create(['absence_id' => $absence->id, 'from' => now(), 'to' => now()->addDay()]);
        $replacement->users()->attach($this->source->id);

        $this->source->mergeInto($this->target);

        $replacement->refresh();
        $this->assertTrue($replacement->users()->where('user_id', $this->target->id)->exists());
        $this->assertFalse($replacement->users()->where('user_id', $this->source->id)->exists());
    }
}
