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
use App\Models\Location;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\RoleService;
use Tests\TestCase;

class ServiceTimezoneSubmissionFeatureTest extends TestCase
{
    protected function makeRequest(Service $service, User $user, array $payload): ServiceRequest
    {
        $baseRequest = ServiceRequest::create(route('service.update', $service->slug), 'PATCH', $payload);
        $request = new class extends ServiceRequest {
            public function authorize()
            {
                return true;
            }
        };
        $request->initialize(
            $baseRequest->query->all(),
            $baseRequest->request->all(),
            $baseRequest->attributes->all(),
            $baseRequest->cookies->all(),
            $baseRequest->files->all(),
            $baseRequest->server->all(),
            $baseRequest->getContent()
        );
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->setUserResolver(fn () => $user);
        $request->validateResolved();

        return $request;
    }

    public function testSummerTimeSubmissionKeepsBerlinTimeAndComputesUtcEnd(): void
    {
        $city = City::factory()->create();
        $location = Location::factory()->create(['city_id' => $city->id]);
        $service = Service::factory()->create(['city_id' => $city->id, 'location_id' => $location->id]);
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $request = $this->makeRequest($service, $user, [
            'date' => '2026-07-29T06:00:00.000Z',
            'time' => '08:00',
            'cc' => 0,
            'location_id' => $location->id,
            'event_class' => 'service',
        ]);

        $validated = $request->validated();

        $this->assertSame('08:00:00', $validated['time']);
        $this->assertSame('2026-07-29 07:00:00', $validated['end']->copy()->setTimezone('UTC')->format('Y-m-d H:i:s'));
    }

    public function testWinterTimeSubmissionKeepsBerlinTimeAndComputesUtcEnd(): void
    {
        $city = City::factory()->create();
        $location = Location::factory()->create(['city_id' => $city->id]);
        $service = Service::factory()->create(['city_id' => $city->id, 'location_id' => $location->id]);
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $request = $this->makeRequest($service, $user, [
            'date' => '2026-01-14T07:00:00.000Z',
            'time' => '08:00',
            'cc' => 0,
            'location_id' => $location->id,
            'event_class' => 'service',
        ]);

        $validated = $request->validated();

        $this->assertSame('08:00:00', $validated['time']);
        $this->assertSame('2026-01-14 08:00:00', $validated['end']->copy()->setTimezone('UTC')->format('Y-m-d H:i:s'));
    }
}
