<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 */

namespace Tests\Feature;

use App\Models\People\User;
use App\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Jobs\MonitorTag;
use Laravel\Horizon\Jobs\StopMonitoringTag;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Verify Horizon's real HTTP authorization without touching operational Redis data. */
class HorizonAuthorizationFeatureTest extends TestCase
{
    /** Check dashboard, API reads and both monitoring writes for each access level. */
    #[DataProvider('accessCases')]
    public function test_horizon_requires_authorization_in_every_environment(string $environment, string $identity): void
    {
        config(['horizon.allowed_emails' => ['operator@example.org'], 'app.url' => 'http://horizon.test']);
        if ($identity !== 'guest') {
            $user = User::factory()->create([
                'email' => $identity === 'allowed' ? 'operator@example.org' : 'other@example.org',
                'must_change_password' => false,
            ]);
            if ($identity === 'super') {
                $user->assignRole(AuthServiceProvider::SUPER);
            }
            $this->actingAs($user);
            self::assertSame(in_array($identity, ['allowed', 'super'], true), Gate::forUser($user)->check('viewHorizon'), 'Existing Horizon gate must recognize the configured operator.');
        }

        Bus::fake([MonitorTag::class, StopMonitoringTag::class]);
        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->andReturn([]);
        $this->app->instance(TagRepository::class, $tags);
        $this->app['env'] = $environment;
        $this->withSession(['_token' => 'horizon-test-token']);
        $this->withHeader('X-CSRF-TOKEN', 'horizon-test-token');
        $allowed = in_array($identity, ['allowed', 'super'], true);
        $status = $allowed ? 200 : 403;

        $this->get('http://horizon.test/horizon')->assertStatus($status);
        $this->getJson('http://horizon.test/horizon/api/monitoring')->assertStatus($status);
        $this->postJson('http://horizon.test/horizon/api/monitoring', ['tag' => 'security-test'])->assertStatus($status);
        $this->deleteJson('http://horizon.test/horizon/api/monitoring/security-test')->assertStatus($status);

        if ($allowed) {
            Bus::assertDispatched(MonitorTag::class);
            Bus::assertDispatched(StopMonitoringTag::class);
        } else {
            Bus::assertNothingDispatched();
        }
    }

    /**
     * Exercise local bypass regression and unchanged production permissions.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function accessCases(): iterable
    {
        foreach (['local', 'production'] as $environment) {
            foreach (['guest', 'denied', 'allowed', 'super'] as $identity) {
                yield $environment.'/'.$identity => [$environment, $identity];
            }
        }
    }
}
