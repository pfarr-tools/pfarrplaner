<?php

namespace Tests\Feature;

use App\Models\People\User;
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
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)
            ->withSession(['adminUserSwitchBack' => $admin->id])
            ->post(route('user.switchback'))
            ->assertRedirect(route('home'));
    }
}
