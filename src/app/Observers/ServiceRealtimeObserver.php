<?php

namespace App\Observers;

use App\Events\ServiceRealtimeChanged;
use App\Models\Service;
use Carbon\Carbon;

/**
 * Publishes service lifecycle changes from the model boundary.
 */
class ServiceRealtimeObserver
{
    /**
     * @param  Service  $service  Newly created service.
     */
    public function created(Service $service): void
    {
        ServiceRealtimeChanged::dispatch('created', $service);
    }

    /**
     * @param  Service  $service  Updated service.
     */
    public function updated(Service $service): void
    {
        ServiceRealtimeChanged::dispatch(
            'updated',
            $service,
            $this->originalCityId($service),
            $this->originalDate($service)
        );
    }

    /**
     * @param  Service  $service  Deleted service.
     */
    public function deleted(Service $service): void
    {
        ServiceRealtimeChanged::dispatch('deleted', $service, (int) $service->city_id, $this->serviceDate($service));
    }

    /**
     * @param  Service  $service  Updated service.
     * @return int|null Previous city identifier.
     */
    private function originalCityId(Service $service): ?int
    {
        $cityId = $service->getOriginal('city_id');

        return $cityId === null ? null : (int) $cityId;
    }

    /**
     * @param  Service  $service  Updated service.
     * @return string|null Previous service date.
     */
    private function originalDate(Service $service): ?string
    {
        $date = $service->getOriginal('date');

        return $date ? Carbon::parse($date)->setTimezone('Europe/Berlin')->toDateString() : null;
    }

    /**
     * @param  Service  $service  Deleted service.
     * @return string|null Current service date in the application timezone.
     */
    private function serviceDate(Service $service): ?string
    {
        return $service->date?->copy()->setTimezone('Europe/Berlin')->toDateString();
    }
}
