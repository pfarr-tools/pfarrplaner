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

namespace Tests\Unit;

use App\Models\People\User;
use App\Models\Service;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\AbstractSimpleModelUnitTest;

class UserUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = User::class;

    protected bool $hasPolicy = true;

    protected bool $hasFactory = true;

    public function test_user_has_admin_cities_relationship(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(BelongsToMany::class, $user->adminCities());
    }

    public function test_user_has_writable_cities_relationship(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(BelongsToMany::class, $user->writableCities());
    }

    public function test_user_full_name_includes_first_and_last_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Maria', 'last_name' => 'Muster']);
        $this->assertStringContainsString('Maria', $user->name);
        $this->assertStringContainsString('Muster', $user->name);
    }

    public function test_user_can_be_created_via_factory(): void
    {
        $user = User::factory()->create();
        $this->assertCount(1, User::all());
        $this->assertNotEmpty($user->last_name);
    }

    /**
     * Includes time-change subscribers when service diffs use their current field names.
     */
    public function test_time_change_subscribers_receive_time_and_day_changes(): void
    {
        $service = Service::factory()->create();
        $subscriber = User::factory()->create();
        $subscriber->subscriptions()->create([
            'city_id' => $service->city_id,
            'subscription_type' => Subscription::SUBSCRIBE_TIME_CHANGES,
        ]);

        foreach ([
            'timeText' => ['original' => '10:00 Uhr', 'changed' => '11:00 Uhr'],
            'day_id' => ['original' => null, 'changed' => 1],
        ] as $attribute => $change) {
            $service->changed = [$attribute => $change];

            $this->assertTrue(
                User::subscribedTo($service)->whereKey($subscriber->id)->exists(),
                'Expected time-only subscribers to receive a '.$attribute.' notification.'
            );
        }

        $service->changed = ['description' => ['original' => 'Alt', 'changed' => 'Neu']];

        $this->assertFalse(User::subscribedTo($service)->whereKey($subscriber->id)->exists());
    }

    protected function setUp(): void
    {
        parent::setUp();
    }
}
