<?php

namespace Tests\Feature;

use App\Mail\User\AccountData;
use App\Models\People\User;
use App\Models\Places\City;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/** Verify legacy password authentication endpoints are no longer registered. */
class PasswordResetFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** Demo selection is an explicit passwordless route that is disabled outside demo mode. */
    public function test_demo_login_route_is_disabled_outside_demo_mode(): void
    {
        config(['app.demo_mode' => false]);

        $this->post(route('login.demo'), ['email' => 'demo@example.com'])
            ->assertNotFound();
    }

    /** Admin-created accounts use an email sign-in link and never send a password. */
    public function test_admin_can_create_an_official_account_without_a_password(): void
    {
        $this->withoutMiddleware();
        Mail::fake();
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $this->actingAs($admin)->post(route('user.store'), [
            'first_name' => 'Neu',
            'last_name' => 'Konto',
            'email' => 'new-account@example.com',
            'isOfficialUser' => true,
        ])->assertRedirect(route('users.index'));

        $user = User::query()->where('email', 'new-account@example.com')->firstOrFail();
        self::assertTrue($user->isOfficialUser);
        Mail::assertQueued(AccountData::class);
        self::assertFalse(property_exists(new AccountData($user, $admin), 'password'));
    }

    /** A person administrator may activate an account through either accepted status field. */
    public function test_non_super_admin_can_activate_accounts_by_submitting_status_fields(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleService::ROLE_ADMIN);
        $target = User::factory()->create([
            'email' => 'person@example.test',
            'is_official_user' => false,
        ]);

        foreach ([
            ['isOfficialUser' => true],
            ['is_official_user' => true],
            ['createAccount' => true],
        ] as $accountFields) {
            $this->actingAs($admin)
                ->patch(route('user.update', $target), [
                    'first_name' => $target->first_name,
                    'last_name' => $target->last_name,
                    'email' => $target->email,
                    ...$accountFields,
                ])
                ->assertRedirect(route('users.index'));
            self::assertTrue($target->fresh()->isOfficialUser);
            $target->update(['is_official_user' => false]);
        }
    }

    /** A person-management permission can create an official account through the quick-add endpoint. */
    public function test_non_super_admin_can_create_official_account_through_quick_add(): void
    {
        $actor = User::factory()->create();
        $permission = Permission::findOrCreate('benutzer-bearbeiten', 'web');
        $actor->givePermissionTo($permission);
        $city = City::factory()->create();

        $this->actingAs($actor)
            ->postJson(route('users.add'), [
                'first_name' => 'Neue',
                'last_name' => 'Person',
                'email' => 'new-person@example.test',
                'isOfficialUser' => true,
                'city_id' => $city->getKey(),
            ])
            ->assertOk()
            ->assertJsonPath('isOfficialUser', true);

        self::assertDatabaseHas('users', [
            'email' => 'new-person@example.test',
            'is_official_user' => true,
        ]);
    }

    /** Password login, reset, and change routes are unavailable. */
    public function test_password_authentication_routes_are_not_registered(): void
    {
        self::assertFalse(Route::has('password.request'));
        self::assertFalse(Route::has('password.email'));
        self::assertFalse(Route::has('password.reset'));
        self::assertFalse(Route::has('password.update'));
        self::assertFalse(Route::has('password.edit'));
        self::assertFalse(Route::has('password.change'));
        self::assertFalse(Route::has('user.password.reset'));

        $this->post('/login', ['email' => 'user@example.com', 'password' => 'anything'])
            ->assertMethodNotAllowed();
        $this->get('/password/reset')->assertNotFound();
    }
}
