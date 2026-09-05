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

use App\Models\Ads\AdChannel;
use App\Models\People\User;
use App\Models\Places\City;
use App\Policies\AdChannelPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdChannelPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private AdChannelPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AdChannelPolicy();
    }

    public function testRegularUserCannotCreateWithoutWriteAccess(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user));
    }

    public function testUserWithWriteAccessCanCreate(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $user->cities()->attach($city->id, ['permission' => 'w']);

        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->create($user, $city));
    }

    public function testUserWithWriteAccessCanUpdate(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $adChannel = AdChannel::factory()->create(['city_id' => $city->id]);
        $user->cities()->attach($city->id, ['permission' => 'w']);

        $this->assertTrue($this->policy->update($user, $adChannel));
    }
}
