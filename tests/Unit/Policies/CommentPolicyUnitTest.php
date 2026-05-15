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

namespace Tests\Unit\Policies;

use App\Models\Comment;
use App\Models\Location;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Policies\CommentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CommentPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private CommentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('gd-bearbeiten');
        $this->policy = new CommentPolicy();
    }

    protected function createServiceForPolicyTest(): Service
    {
        $city = City::factory()->create();
        $location = Location::factory()->create(['city_id' => $city->id]);

        return Service::factory()->create([
            'city_id' => $city->id,
            'location_id' => $location->id,
        ]);
    }

    public function testUserWithoutServiceWritePermissionCannotCreateComment(): void
    {
        $user = User::factory()->create();
        $service = $this->createServiceForPolicyTest();

        $this->assertFalse($this->policy->create($user, $service));
    }

    public function testUserWithServiceWritePermissionCanCreateComment(): void
    {
        $user = User::factory()->create();
        $service = $this->createServiceForPolicyTest();
        $user->givePermissionTo('gd-bearbeiten');
        $user->cities()->attach($service->city_id, ['permission' => 'w']);

        $this->assertTrue($this->policy->create($user, $service));
    }

    public function testUserWithoutServiceWritePermissionCannotDeleteComment(): void
    {
        $user = User::factory()->create();
        $service = $this->createServiceForPolicyTest();
        $comment = Comment::factory()->create([
            'commentable_id' => $service->id,
            'commentable_type' => Service::class,
        ]);

        $this->assertFalse($this->policy->delete($user, $comment));
    }

    public function testUserWithServiceWritePermissionCanDeleteComment(): void
    {
        $user = User::factory()->create();
        $service = $this->createServiceForPolicyTest();
        $comment = Comment::factory()->create([
            'commentable_id' => $service->id,
            'commentable_type' => Service::class,
        ]);
        $user->givePermissionTo('gd-bearbeiten');
        $user->cities()->attach($comment->commentable->city_id, ['permission' => 'w']);

        $this->assertTrue($this->policy->delete($user, $comment));
    }
}
