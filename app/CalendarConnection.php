<?php

namespace App;

use App\Calendars\SyncEngines\SyncEngines;
use App\Casts\EncryptedAttribute;
use AustinHeap\Database\Encryption\Traits\HasEncryptedAttributes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CalendarConnection extends Model
{
    public const CONNECTION_TYPE_OWN = 1;
    public const CONNECTION_TYPE_ALL = 2;

    protected $fillable = [
        'user_id',
        'title',
        'include_hidden',
        'include_alternate',
        'include_vacations',
        'include_rite_anniversaries',
    ];

    protected $with = ['user', 'cities'];
    protected $appends = ['uri'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function cities()
    {
        return $this->belongsToMany(City::class)->withPivot('connection_type');
    }

    public function getUriAttribute() {
        return url('/dav/calendars/'.$this->user->email.'/'.Str::slug($this->title)).'/';
    }

    /**
     * Get all services for a city that should be synced in a full sync
     * @param City $city
     * @param false $countOnly
     * @return \Illuminate\Support\Collection|int
     */
    public function getSyncableServicesForCity(City $city, $countOnly = false)
    {
        $cityServices = Service::setEagerLoads([])->with([])->startingFrom(Carbon::now()->subMonths(12));
        switch ($city->pivot->connection_type) {
            case 0:
                $cityServices = new \Illuminate\Support\Collection();
                return ($countOnly ? 0 : $cityServices);
                break;
            case self::CONNECTION_TYPE_ALL:
                $cityServices = $cityServices->inCity($city);
                break;
            case self::CONNECTION_TYPE_OWN:
                $cityServices = $cityServices->inCity($city)->userParticipates($this->user);
        }

        // exclude hidden service if necessary
        if (!$this->include_hidden) {
            $cityServices->notHidden();
        }

        return $countOnly ? $cityServices->count() : $cityServices->get();
    }

    /**
     * Get all services to be synced
     * @return Collection
     */
    public function getSyncableServices(): Collection
    {
        $services = collect();
        foreach ($this->cities as $city) {
            $services = $services->merge($this->getSyncableServicesForCity($city));
        }
        return $services;
    }

    public function getCurrentSyncToken()
    {
        return max(
            $this->getSyncableServices()->pluck('updated_at')->max(),
            $this->getSyncableAbsences()->pluck('updated_at')->max(),
        )->toIsoString();
    }

    public function getCalendarItems()
    {
        $items = [];

        foreach($this->getSyncableAbsences() as $absence) {
            try {
                foreach ($absence->getCalendarItems(false, false) as $item) {
                    $items[] = $item;
                }
            } catch (\RuntimeException $e) {
            }
        }

        foreach ($this->getSyncableServices() as $service) {
            try {
                foreach ($service->getCalendarItems($this->include_alternate, $this->include_rite_anniversaries) as $item) {
                    $items[] = $item;
                }
            } catch (\RuntimeException $e) {
            }
        }


        return $items;
    }


    /**
     * Get all CalendarConnections that should be concerned with a service
     * @param Service $service
     * @return mixed
     */
    public static function getConnectionsForService(Service $service)
    {
        // get connections requiring all services of this city
        $calendarConnections = CalendarConnection::whereHas(
            'cities',
            function ($query) use ($service) {
                $query->where('cities.id', $service->city->id)
                    ->where('calendar_connection_city.connection_type', CalendarConnection::CONNECTION_TYPE_ALL);
            }
        )->get();

        // get connections requiring services for a specific participant
        foreach ($service->participants as $participant) {
            $calendarConnections = $calendarConnections->merge(
                CalendarConnection::whereHas(
                    'cities',
                    function ($query) use ($service) {
                        $query->where('cities.id', $service->city->id)
                            ->where(
                                'calendar_connection_city.connection_type',
                                CalendarConnection::CONNECTION_TYPE_OWN
                            );
                    }
                )->where('user_id', $participant->id)->get()
            );
        }

        return $calendarConnections;
    }

    public function getSyncableAbsences()
    {
        if (!$this->include_vacations) {
            return collect([]);
        }

        switch ($this->include_vacations) {
            case 1:
                $own = Absence::where('user_id', $this->user_id)->where('to', '>=', Carbon::now()->subYear(1))->get();
                $replacing = Absence::userIsReplacement($this->user)->get();
                return $own->merge($replacing);
        }
    }

}
