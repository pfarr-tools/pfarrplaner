<?php

namespace Tests\Unit\Events;

use App\Events\ServiceRealtimeChanged;
use App\Models\Places\City;
use App\Models\Service;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class ServiceRealtimeChangedUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_pusher_broadcaster_dependency_is_available(): void
    {
        $this->assertInstanceOf(
            PusherBroadcaster::class,
            Broadcast::connection('pusher')
        );
    }

    public function test_update_event_contains_only_the_service_invalidation_contract(): void
    {
        $city = City::factory()->create();
        $oldCity = City::factory()->create();
        $service = Service::factory()->create(['city_id' => $city->id]);

        $event = new ServiceRealtimeChanged('updated', $service, $oldCity->id, '2026-11-01');

        $this->assertSame('service.changed', $event->broadcastAs());
        $this->assertSame([
            'change' => 'updated',
            'serviceId' => $service->id,
            'cityId' => $city->id,
            'date' => $service->date->copy()->setTimezone('Europe/Berlin')->toDateString(),
            'oldCityId' => $oldCity->id,
            'oldDate' => '2026-11-01',
        ], $event->broadcastWith());
        $this->assertSame(
            ['private-services.city.'.$city->id, 'private-services.city.'.$oldCity->id],
            array_map(static fn ($channel) => $channel->name, $event->broadcastOn())
        );
    }
}
