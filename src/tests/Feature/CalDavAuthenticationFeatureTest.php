<?php

namespace Tests\Feature;

use App\Http\Controllers\DAV\Backends\AuthBackend;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/** Verify CalDAV accepts dedicated API tokens but never account passwords. */
final class CalDavAuthenticationFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** The DAV endpoint rejects legacy account passwords at the HTTP boundary. */
    public function test_dav_http_basic_rejects_legacy_account_passwords(): void
    {
        $user = User::factory()->create([
            'email' => 'calendar@example.test',
            'is_official_user' => true,
            'password' => 'legacy-password',
        ]);
        DB::table('calendar_connections')->insert([
            'user_id' => $user->getKey(),
            'title' => 'Kalender',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withServerVariables([
            'PHP_AUTH_USER' => $user->email,
            'PHP_AUTH_PW' => 'legacy-password',
        ])->get('/dav/')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate');
    }

    /** A calendar user's dedicated token authenticates while their old password does not. */
    public function test_caldav_uses_api_tokens_instead_of_passwords(): void
    {
        $token = 'dav-api-token-with-sufficient-entropy';
        $user = User::factory()->create([
            'email' => 'calendar@example.test',
            'is_official_user' => true,
            'password' => 'legacy-password',
            'api_token' => hash('sha256', $token),
        ]);
        DB::table('calendar_connections')->insert([
            'user_id' => $user->getKey(),
            'title' => 'Kalender',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $method = new ReflectionMethod(AuthBackend::class, 'validateUserPass');
        $backend = new AuthBackend;

        self::assertTrue($method->invoke($backend, $user->email, $token));
        self::assertAuthenticatedAs($user);

        Auth::logout();
        self::assertFalse($method->invoke($backend, $user->email, 'legacy-password'));
        self::assertGuest();
    }
}
