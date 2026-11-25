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
 *
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Models\Calendar\External;

use App\DAV\DAVCalendarItem;
use App\Models\Leave\Absence;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Database\Factories\Calendar\External\CalendarConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** External calendar connection for a user. */
class CalendarConnection extends Model
{
    /** @use HasFactory<CalendarConnectionFactory> */
    use HasFactory;

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
     * @return BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<City, $this>
     */
    public function cities()
    {
        return $this->belongsToMany(City::class)->withPivot('connection_type');
    }

    /**
     * Gibt die CalDAV-URI dieser Verbindung zurück.
     *
     * @return string CalDAV-URI der Verbindung.
     */
    public function getUriAttribute(): string
    {
        $user = $this->user ?? throw new \LogicException('Calendar connection has no owner.');

        return url('/dav/calendars/'.$user->email.'/'.Str::slug($this->title)).'/';
    }

    /**
     * Get all services for a city that should be synced in a full sync
     *
     * @param  bool  $countOnly  Return a count instead of models.
     * @return ($countOnly is true ? int : Collection<int, Service>)
     */
    public function getSyncableServicesForCity(City $city, $countOnly = false)
    {
        $cityServices = Service::setEagerLoads([])->with([])->startingFrom(Carbon::now()->subMonths(12));
        switch ($city->pivot->connection_type) {
            case 0:
                $cityServices = new Collection;

                return $countOnly ? 0 : $cityServices;
            case self::CONNECTION_TYPE_ALL:
                $cityServices = $cityServices->inCity($city);
                break;
            case self::CONNECTION_TYPE_OWN:
                $user = $this->user;
                if ($user === null) {
                    return $countOnly ? 0 : new Collection;
                }
                $cityServices = $cityServices->inCity($city)->userParticipates($user);
        }

        // exclude hidden service if necessary
        if (! $this->include_hidden) {
            $cityServices->notHidden();
        }

        return $countOnly ? $cityServices->count() : $cityServices->get();
    }

    /**
     * Get all services to be synced
     *
     * @return Collection<int, Service>
     */
    public function getSyncableServices(): Collection
    {
        $services = collect();
        foreach ($this->cities as $city) {
            /** @var Collection<int, Service> $cityServices */
            $cityServices = $this->getSyncableServicesForCity($city);
            $services = $services->merge($cityServices);
        }

        return $services;
    }

    /**
     * Gibt den aktuellen Synchronisierungs-Token der Verbindung zurück.
     *
     * @return string ISO-8601-Zeitstempel der letzten Änderung.
     */
    public function getCurrentSyncToken(): string
    {
        return max(
            $this->getSyncableServices()->pluck('updated_at')->max(),
            $this->getSyncableAbsences()->pluck('updated_at')->max(),
        )->toIsoString();
    }

    /**
     * Gibt alle CalDAV-Einträge dieser Verbindung zurück.
     *
     * @return DAVCalendarItem[] CalDAV-Einträge aus Abwesenheiten und Gottesdiensten.
     */
    public function getCalendarItems(): array
    {
        $items = [];

        foreach ($this->getSyncableAbsences() as $absence) {
            try {
                foreach ($absence->getCalendarItems(false, false) as $item) {
                    $items[] = $item;
                }
            } catch (\RuntimeException $e) {
            }
        }

        foreach ($this->getSyncableServices() as $service) {
            try {
                foreach (
                    $service->getCalendarItems(
                        $this->include_alternate,
                        (bool) $this->include_rite_anniversaries
                    ) as $item
                ) {
                    $items[] = $item;
                }
            } catch (\RuntimeException $e) {
            }
        }

        return $items;
    }

    /**
     * Get all CalendarConnections that should be concerned with a service
     *
     * @return mixed
     */
    public static function getConnectionsForService(Service $service)
    {
        $city = $service->city;
        if ($city === null) {
            return collect();
        }

        // get connections requiring all services of this city
        $calendarConnections = CalendarConnection::whereHas(
            'cities',
            function ($query) use ($city) {
                $query->where('cities.id', $city->id)
                    ->where('calendar_connection_city.connection_type', CalendarConnection::CONNECTION_TYPE_ALL);
            }
        )->get();

        // get connections requiring services for a specific participant
        foreach ($service->participants as $participant) {
            $calendarConnections = $calendarConnections->merge(
                CalendarConnection::whereHas(
                    'cities',
                    function ($query) use ($city) {
                        $query->where('cities.id', $city->id)
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

    /**
     * Gibt die über diese Verbindung synchronisierten Abwesenheiten zurück.
     *
     * @return Collection<int, Absence> Eigene Abwesenheiten und Vertretungen.
     */
    public function getSyncableAbsences(): Collection
    {
        $absences = collect([]);

        if (! $this->include_vacations) {
            return $absences;
        }

        switch ($this->include_vacations) {
            case 1:
                $own = Absence::where('user_id', $this->user_id)->where('to', '>=', Carbon::now()->subYear())->get();
                $user = $this->user;
                $replacing = $user === null ? collect() : Absence::userIsReplacement($user)->get();

                return $own->merge($replacing);
        }

        return $absences;
    }
}
