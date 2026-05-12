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

use App\Http\Requests\ServiceRequest;
use App\Models\People\User;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ServiceRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function testRulesMethodReturnsArray(): void
    {
        $request = new ServiceRequest();
        $rules = $request->rules();
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('time', $rules);
    }

    public function testValidTimeFormatPasses(): void
    {
        $request = new ServiceRequest();
        $validator = Validator::make(['time' => '10:00'], $request->rules());
        $this->assertTrue($validator->passes());
    }

    public function testInvalidTimeFormatFails(): void
    {
        $request = new ServiceRequest();
        $validator = Validator::make(['time' => '25:00'], $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('time', $validator->errors()->toArray());
    }

    public function testValidOfferingTypePasses(): void
    {
        $request = new ServiceRequest();
        $validator = Validator::make(['offering_type' => 'eO'], $request->rules());
        $this->assertTrue($validator->passes());
    }

    public function testInvalidOfferingTypeFails(): void
    {
        $request = new ServiceRequest();
        $validator = Validator::make(['offering_type' => 'invalid'], $request->rules());
        $this->assertFalse($validator->passes());
    }

    public function testUpdateWithValidDataSucceeds(): void
    {
        $service = Service::factory()->create();
        $this->actingAs($this->user)
            ->patch(route('service.update', Service::first()->slug), [
                'description' => 'Testgottesdienst',
            ])
            ->assertStatus(302);
    }
}
