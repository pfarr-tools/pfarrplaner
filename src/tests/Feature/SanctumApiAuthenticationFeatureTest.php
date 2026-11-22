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

use App\Http\Resources\UserResource;
use App\Models\People\User;
use App\Models\Places\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/** Verify session, Sanctum-token, and legacy-token API authentication paths. */
final class SanctumApiAuthenticationFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** First-party browser sessions authenticate stateful API requests. */
    public function test_stateful_browser_session_can_call_the_api(): void
    {
        $user = $this->userWithVisibleCity();

        $this->actingAs($user)
            ->withHeader('Origin', 'http://localhost')
            ->getJson(route('api.tab.count', ['tab' => '0']))
            ->assertOk();
    }

    /** A Sanctum personal access token authenticates an external API client. */
    public function test_personal_access_token_can_call_the_api(): void
    {
        $user = $this->userWithVisibleCity();
        $token = $user->createToken('integration')->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.tab.count', ['tab' => '0']))
            ->assertOk();
    }

    /** Existing raw database tokens remain valid during the migration. */
    public function test_legacy_plaintext_token_remains_valid(): void
    {
        $token = 'legacy-plain-token-for-api-compatibility';
        $user = $this->userWithVisibleCity(['api_token' => $token]);

        $this->withToken($token)
            ->getJson(route('api.tab.count', ['tab' => '0']))
            ->assertOk();
    }

    /** Existing raw clients keep working when their stored token is hashed. */
    public function test_legacy_plaintext_token_matches_its_stored_hash(): void
    {
        $token = 'legacy-hashed-token-for-api-compatibility';
        $user = $this->userWithVisibleCity(['api_token' => hash('sha256', $token)]);

        $this->withToken($token)
            ->getJson(route('api.tab.count', ['tab' => '0']))
            ->assertOk();
    }

    /** Clients holding the old exposed digest keep working during migration. */
    public function test_legacy_stored_digest_remains_valid(): void
    {
        $storedToken = hash('sha256', 'legacy-raw-token-for-digest-compatibility');
        $this->userWithVisibleCity(['api_token' => $storedToken]);

        $this->withToken($storedToken)
            ->getJson(route('api.tab.count', ['tab' => '0']))
            ->assertOk();
    }

    /** The one-time migration hashes plaintext tokens but preserves old digests. */
    public function test_migration_hashes_legacy_tokens_without_rehashing_digests(): void
    {
        $plainToken = 'legacy-plaintext-token-for-migration';
        $storedDigest = hash('sha256', 'already-hashed-token-for-migration');
        $plainUser = $this->userWithVisibleCity(['api_token' => $plainToken]);
        $digestUser = User::factory()->create(['api_token' => $storedDigest]);
        $migration = require database_path('migrations/2026_11_22_000001_hash_legacy_user_api_tokens.php');

        $migration->up();

        $this->assertSame(hash('sha256', $plainToken), $plainUser->fresh()->api_token);
        $this->assertSame($storedDigest, $digestUser->fresh()->api_token);

        $this->withToken($plainToken)
            ->getJson(route('api.tab.count', ['tab' => '0']))
            ->assertOk();
    }

    /** Calendar subscription requests issue a persisted Sanctum credential. */
    public function test_calendar_link_token_is_created_from_the_authenticated_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('ical.token'))
            ->assertOk()
            ->assertJsonStructure(['token']);

        $token = $response->json('token');
        $personalAccessToken = PersonalAccessToken::findToken($token);

        $this->assertNotNull($personalAccessToken);
        $this->assertSame($user->getKey(), $personalAccessToken->tokenable_id);
        $this->assertSame(['calendar:feed'], $personalAccessToken->abilities);

        $this->app['auth']->guard('web')->logout();
        $this->withToken($token)
            ->getJson(route('api.tab.count', ['tab' => '0']))
            ->assertUnauthorized();
    }

    /** User resource props never disclose legacy API credentials. */
    public function test_user_resource_does_not_expose_api_token(): void
    {
        $user = User::factory()->create(['api_token' => 'legacy-token-value']);

        $this->assertArrayNotHasKey('api_token', UserResource::make($user)->resolve());
    }

    /** Create a user with the city and settings needed by the API tab endpoint. */
    private function userWithVisibleCity(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $city = City::factory()->create();
        $user->cities()->attach($city->id);
        $user->setSetting('homeScreenTabsConfig', [
            'tabs' => [['type' => 'nextServices', 'config' => ['mine' => 0]]],
        ]);

        return $user;
    }
}
