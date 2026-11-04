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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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

    public function testIndexLoads(): void
    {
        $this->actingAs($this->user)
            ->get(route('users.index'))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page
                ->component('Admin/User/Index')
                ->has('users', 1)
                ->missing('users.0.address')
                ->missing('users.0.api_token')
                ->missing('users.0.created_at')
                ->missing('users.0.updated_at')
            );
    }

    public function testEditorLoads(): void
    {
        $target = User::factory()->create();
        $this->actingAs($this->user)
            ->get(route('user.edit', $target->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page
                ->component('Admin/User/UserEditor')
                ->has('users', 2)
                ->missing('users.0.address')
                ->missing('users.0.api_token')
                ->missing('user.password')
                ->missing('user.created_at')
                ->missing('user.updated_at')
            );
    }

    public function testJoinLoadsCompactPeople(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->user)
            ->get(route('user.join', $target->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page
                ->component('Admin/User/Join')
                ->has('people', 1)
                ->missing('user.address')
                ->missing('people.0.address')
                ->missing('people.0.api_token')
            );
    }

    public function testUpdatingUserProfileWithoutPasswordKeepsExistingPasswordHash(): void
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

}
