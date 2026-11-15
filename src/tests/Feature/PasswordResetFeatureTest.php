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
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Verify that the framework-provided password-reset routes render usable forms.
 */
class PasswordResetFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The password-reset request form is available to guests.
     */
    public function test_password_reset_request_form_is_available(): void
    {
        $this->get('/password/reset')
            ->assertOk()
            ->assertSee('Passwort zurücksetzen');
    }

    /**
     * The password-reset form accepts the token supplied by the broker.
     */
    public function test_password_reset_form_is_available_for_a_token(): void
    {
        $this->get('/password/reset/example-token')
            ->assertOk()
            ->assertSee('Neues Passwort');
    }

    /**
     * The reset endpoint rejects a password missing the required symbol.
     */
    public function test_password_reset_requires_a_strong_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->post('/password/reset', [
            'token' => 'example-token',
            'email' => 'unknown@example.com',
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
        ])->assertSessionHasErrors('password');
    }

    /**
     * A successful public reset clears the forced password-change flag.
     */
    public function test_successful_password_reset_clears_forced_change_flag(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'Old1!',
            'must_change_password' => true,
        ]);
        $token = Password::broker()->createToken($user);

        $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'New1!word',
            'password_confirmation' => 'New1!word',
        ])->assertRedirect();

        $this->assertTrue(password_verify('New1!word', $user->fresh()->password));
        $this->assertFalse((bool) $user->fresh()->must_change_password);
    }
}
