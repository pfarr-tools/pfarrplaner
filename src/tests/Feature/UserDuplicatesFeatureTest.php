<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 */

namespace Tests\Feature;

use App\Models\Leave\Absence;
use App\Models\People\Participant;
use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDuplicatesFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $this->regularUser = User::factory()->create();
    }

    public function testSuperAdminCanAccessFindDuplicates()
    {
        $response = $this->actingAs($this->superAdmin)->get(route('users.duplicates'));
        $response->assertStatus(200);
    }

    public function testNonSuperAdminCannotAccessFindDuplicates()
    {
        $response = $this->actingAs($this->regularUser)->get(route('users.duplicates'));
        $response->assertStatus(403);
    }

    public function testSuperAdminCanPostFixDuplicates()
    {
        $source = User::factory()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('users.duplicates.fix'), ['groups' => [['target_id' => $target->id, 'source_ids' => [$source->id]]]]);

        $response->assertRedirect(route('users.index'));
    }

    public function testNonSuperAdminCannotPostFixDuplicates()
    {
        $source = User::factory()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->post(route('users.duplicates.fix'), ['groups' => [['target_id' => $target->id, 'source_ids' => [$source->id]]]]);

        $response->assertStatus(403);
    }

    public function testFixDuplicatesMergesUsersAndDeletesSource()
    {
        $source = User::factory()->create(['first_name' => 'Max', 'last_name' => 'Mustermann']);
        $target = User::factory()->create(['first_name' => 'Max', 'last_name' => 'Mustermann']);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('users.duplicates.fix'), ['groups' => [['target_id' => $target->id, 'source_ids' => [$source->id]]]]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $source->id]);
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function testFixDuplicatesTransfersParticipants()
    {
        $source = User::factory()->create();
        $target = User::factory()->create();

        $service = \App\Models\Service::factory()->create([
            'city_id' => \App\Models\Places\City::factory()->create()->id,
        ]);
        $source->services()->attach($service->id, ['category' => 'P']);

        $this->actingAs($this->superAdmin)
            ->post(route('users.duplicates.fix'), ['groups' => [['target_id' => $target->id, 'source_ids' => [$source->id]]]]);

        $this->assertEquals(1, Participant::where('user_id', $target->id)->count());
        $this->assertEquals(0, Participant::where('user_id', $source->id)->count());
    }

    public function testFixDuplicatesTransfersAbsences()
    {
        $source = User::factory()->create();
        $target = User::factory()->create();

        Absence::create(['user_id' => $source->id, 'from' => now(), 'to' => now()->addDay(), 'reason' => 'Urlaub']);

        $this->actingAs($this->superAdmin)
            ->post(route('users.duplicates.fix'), ['groups' => [['target_id' => $target->id, 'source_ids' => [$source->id]]]]);

        $this->assertEquals(1, Absence::where('user_id', $target->id)->count());
        $this->assertEquals(0, Absence::where('user_id', $source->id)->count());
    }

    public function testFixDuplicatesHandlesNonExistentSourceGracefully()
    {
        $target = User::factory()->create();
        $nonExistentId = 99999;

        $response = $this->actingAs($this->superAdmin)
            ->post(route('users.duplicates.fix'), ['groups' => [['target_id' => $target->id, 'source_ids' => [$nonExistentId]]]]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function testFixDuplicatesHandlesNonExistentTargetGracefully()
    {
        $source = User::factory()->create();
        $nonExistentTargetId = 99999;

        $response = $this->actingAs($this->superAdmin)
            ->post(route('users.duplicates.fix'), ['groups' => [['target_id' => $nonExistentTargetId, 'source_ids' => [$source->id]]]]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $source->id]);
    }

    public function testFixDuplicatesSkipsSelfMerge()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('users.duplicates.fix'), ['groups' => [['target_id' => $user->id, 'source_ids' => [$user->id]]]]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function testFixDuplicatesMergesMultipleSourcesIntoOneTarget()
    {
        $target = User::factory()->create();
        $sourceA = User::factory()->create();
        $sourceB = User::factory()->create();

        $this->actingAs($this->superAdmin)
            ->post(route('users.duplicates.fix'), ['groups' => [['target_id' => $target->id, 'source_ids' => [$sourceA->id, $sourceB->id]]]]);

        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('users', ['id' => $sourceA->id]);
        $this->assertDatabaseMissing('users', ['id' => $sourceB->id]);
    }

    public function testFindDuplicatesGroupsUsersWithSameName()
    {
        User::factory()->create(['first_name' => 'Erika', 'last_name' => 'Musterfrau']);
        User::factory()->create(['first_name' => 'Erika', 'last_name' => 'Musterfrau']);
        User::factory()->create(['first_name' => 'Unique', 'last_name' => 'Person']);

        $response = $this->actingAs($this->superAdmin)->get(route('users.duplicates'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/User/DuplicatesWizard')
            ->has('possibleDuplicates')
            ->has('withoutDuplicates')
        );
    }
}
