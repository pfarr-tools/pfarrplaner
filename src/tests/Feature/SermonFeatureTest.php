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

use App\Models\People\User;
use App\Models\Sermon;
use App\Models\Service;
use App\Services\RoleService;
use App\Http\Middleware\ForceDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SermonFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ForceDomain::class);
        Storage::fake();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function testEditorLoads(): void
    {
        $service = Service::factory()->create();
        $sermon = Sermon::create(['title' => 'Testpredigt']);
        $service->update(['sermon_id' => $sermon->id]);
        $this->actingAs($this->user)
            ->get(route('sermon.editor', $sermon->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('sermonEditor'));
    }

    public function testUserWithServicePermissionCanAttachSermonImage(): void
    {
        $service = Service::factory()->create();
        $sermon = Sermon::create(['title' => 'Testpredigt']);
        $service->update(['sermon_id' => $sermon->id]);

        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $user->cities()->attach($service->city_id, ['permission' => 'w']);

        $response = $this->actingAs($user)->post(route('sermon.image.attach', ['model' => $sermon->id]), [
            'attachments' => [UploadedFile::fake()->image('sermon.jpg')],
        ]);

        $response->assertOk();
        $sermon->refresh();
        $this->assertNotEmpty($sermon->image);
        $this->assertStringStartsWith('attachments/', $sermon->image);
    }
}
