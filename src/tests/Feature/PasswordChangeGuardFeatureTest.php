<?php

namespace Tests\Feature;

use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Verify that forced password changes cannot be bypassed through web routes. */
class PasswordChangeGuardFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** Protected application routes redirect users with a forced change. */
    public function test_forced_password_change_redirects_protected_routes(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)
            ->get(route('user.profile'))
            ->assertRedirect(route('password.edit', ['from' => 'user.profile']));
    }

    /** The password form remains reachable while the change is required. */
    public function test_forced_password_change_allows_password_routes(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)
            ->get(route('password.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/PasswordChanger'));
    }

    /** An impersonated account can return to its administrator. */
    public function test_impersonated_forced_change_can_switch_back(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_ADMIN);
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)
            ->withSession(['adminUserSwitchBack' => $admin->id])
            ->get(route('user.profile'))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['adminUserSwitchBack' => $admin->id])
            ->post(route('user.switchback'))
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($admin);
    }

    /** A successful forced password change removes the guard. */
    public function test_successful_password_change_removes_the_guard(): void
    {
        $user = User::factory()->create([
            'password' => 'Current1!',
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->post(route('password.change'), [
                'current_password' => 'Current1!',
                'new_password' => 'Newpass1!',
                'new_password_confirmation' => 'Newpass1!',
            ])
            ->assertRedirect(route('home'));

        $this->assertFalse((bool) $user->fresh()->must_change_password);
        $this->actingAs($user)->get(route('user.profile'))->assertOk();
    }

    /** Logout and CSRF keepalive remain available during a forced change. */
    public function test_logout_and_csrf_keepalive_are_exempt(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)
            ->get(route('csrf.keepalive'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));
    }

    /** Inertia requests receive the same safe password-form redirect. */
    public function test_inertia_requests_are_redirected_to_password_change(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->get(route('user.profile'))
            ->assertRedirect(route('password.edit', ['from' => 'user.profile']));
    }

    /** The proxy exception disappears after the real switch-back lifecycle. */
    public function test_switch_back_clears_the_proxy_exception(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_ADMIN);
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($admin)
            ->post(route('user.switch', $user))
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('user.profile'))->assertOk();

        $this->post(route('user.switchback'))->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($admin);
    }
}
