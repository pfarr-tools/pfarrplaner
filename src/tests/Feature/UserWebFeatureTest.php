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

use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserWebFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function test_index_loads(): void
    {
        $this->actingAs($this->user)
            ->get(route('users.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/User/Index')
                ->has('users', 1)
                ->missing('users.0.address')
                ->missing('users.0.api_token')
                ->missing('users.0.created_at')
                ->missing('users.0.updated_at')
            );
    }

    public function test_editor_loads(): void
    {
        $target = User::factory()->create();
        $this->actingAs($this->user)
            ->get(route('user.edit', $target->id))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/User/UserEditor')
                ->has('users', 2)
                ->missing('users.0.address')
                ->missing('users.0.api_token')
                ->missing('user.password')
                ->missing('user.created_at')
                ->missing('user.updated_at')
            );
    }

    public function test_join_loads_compact_people(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->user)
            ->get(route('user.join', $target->id))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/User/Join')
                ->has('people', 1)
                ->missing('user.address')
                ->missing('people.0.address')
                ->missing('people.0.api_token')
            );
    }

    public function test_updating_user_profile_without_password_keeps_existing_password_hash(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $target = User::factory()->create(['password' => 'long-term-password']);
        $passwordHash = $target->getRawOriginal('password');

        $this->actingAs($this->user)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => $target->email,
                'password' => null,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertSame($passwordHash, $target->fresh()->getRawOriginal('password'));
    }

    public function test_updating_profile_without_email_keeps_existing_password_hash(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $target = User::factory()->create(['email' => null, 'password' => 'long-term-password']);
        $passwordHash = $target->getRawOriginal('password');

        $this->actingAs($this->user)
            ->patch(route('user.update', $target), [
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => null,
                'password' => null,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertSame($passwordHash, $target->fresh()->getRawOriginal('password'));
    }

    public function test_profile_password_change_keeps_current_hash_until_validation(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->user->update(['password' => 'testtest', 'must_change_password' => 1]);

        $this->actingAs($this->user)
            ->patch(route('user.profile.save'), [
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'email' => $this->user->email,
                'current_password' => 'testtest',
                'new_password' => 'Voca1234',
                'new_password_confirmation' => 'Voca1234',
            ])
            ->assertRedirect(route('home'));

        $this->assertTrue(Hash::check('Voca1234', $this->user->fresh()->password));
    }

    public function test_initial_password_can_be_changed_without_current_password(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->user->update(['password' => 'testtest', 'must_change_password' => 1]);

        $this->actingAs($this->user)
            ->post(route('password.change'), [
                'new_password' => 'Voca1234',
                'new_password_confirmation' => 'Voca1234',
            ])
            ->assertRedirect(route('home'));

        $this->assertTrue(Hash::check('Voca1234', $this->user->fresh()->password));
        $this->assertFalse((bool) $this->user->fresh()->must_change_password);
    }
}
