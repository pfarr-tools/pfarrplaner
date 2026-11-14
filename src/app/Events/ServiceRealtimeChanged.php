<?php

namespace App\Events;

use App\Models\Service;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast a small invalidation message for a changed service.
 */
class ServiceRealtimeChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** Lifecycle change name. */
    public string $change;

    /** Changed service identifier. */
    public int $serviceId;

    /** Current city identifier. */
    public ?int $cityId;

    /** Current local calendar date. */
    public ?string $date;

    /** Previous city identifier. */
    public ?int $oldCityId;

    /** Previous local calendar date. */
    public ?string $oldDate;

    /** City identifiers receiving the invalidation. */
    private array $channelCityIds;

    /**
     * @param  string  $change  Lifecycle change name.
     * @param  Service  $service  Changed service.
     * @param  int|null  $oldCityId  Previous city identifier.
     * @param  string|null  $oldDate  Previous service date.
     */
    public function __construct(
        string $change,
        Service $service,
        ?int $oldCityId = null,
        ?string $oldDate = null
    ) {
        $this->change = $change;
        $this->serviceId = (int) $service->getKey();
        $this->cityId = $service->city_id ? (int) $service->city_id : null;
        $this->date = $service->date?->copy()->setTimezone('Europe/Berlin')->toDateString();
        $this->oldCityId = $oldCityId;
        $this->oldDate = $oldDate;
        $this->channelCityIds = array_values(array_unique(array_filter(array_merge(
            [$this->cityId, $this->oldCityId],
            $service->relatedCities()->pluck('cities.id')->map(fn ($id): int => (int) $id)->all()
        ))));
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return array_map(
            static fn (int $cityId): PrivateChannel => new PrivateChannel('services.city.'.$cityId),
            $this->channelCityIds
        );
    }

    /** Return the public broadcast event name. */
    public function broadcastAs(): string
    {
        return 'service.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'change' => $this->change,
            'serviceId' => $this->serviceId,
            'cityId' => $this->cityId,
            'date' => $this->date,
            'oldCityId' => $this->oldCityId,
            'oldDate' => $this->oldDate,
        ];
    }
}
