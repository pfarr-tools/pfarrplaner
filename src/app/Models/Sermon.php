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

namespace App\Models;

use App\Models\Places\City;
use App\Traits\HasAttachedImage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Sermon extends Model
{
    use HasAttachedImage;

    /** @var string Name des Bildfeldes in der Datenbank. */
    protected $imageField = 'image';

    protected $guarded = [];

    protected $appends = ['fullTitle'];

    /**
     * Return the services associated with this sermon.
     *
     * @return HasMany<Service, $this>
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Initialize the model class
     *
     * - Register created/updated events
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();
        static::created(function (Sermon $sermon) {
            $sermon->handleRelatedFunerals();
        });
        static::updated(function (Sermon $sermon) {
            $sermon->handleRelatedFunerals();
        });
    }

    /**
     * Set funeral text for related funerals
     *
     * @return void
     */
    public function handleRelatedFunerals()
    {
        foreach ($this->services as $service) {
            foreach ($service->funerals as $funeral) {
                if (empty($funeral->text)) {
                    $funeral->update(['text' => $this->reference]);
                }
            }
        }
    }

    /**
     * Gibt alle Prediger dieser Predigt nach Gottesdienst-ID gruppiert zurück.
     *
     * @return array<int|string, array<array-key, mixed>|Collection<array-key, mixed>> Prediger je Gottesdienst-ID.
     */
    public function getPreachersAttribute(): array
    {
        $preachers = [];
        foreach ($this->services as $service) {
            $preachers[$service->id] = $service->preachers;
        }

        return $preachers;
    }

    /**
     * Get accessible sermons, i.e. those preached in the same city, or by the currently logged-in user
     *
     * @return array<int|string, Sermon> Sermons keyed by sortable service date.
     */
    public static function getList(City $city)
    {
        $sermons = self::all()->filter(function ($value, $key) use ($city) {
            foreach ($value->services as $service) {
                $user = Auth::user();
                if ($service->city?->id == $city->id) {
                    return true;
                }
                if (collect($service->pastors->pluck('id'))->contains($user?->id)) {
                    return true;
                }
            }
        });
        $list = [];
        foreach ($sermons as $sermon) {
            foreach ($sermon->services as $service) {
                $sermon->setAttribute('name', $service->oneLiner().' "'.$sermon->title
                    .($sermon->subtitle ? ': '.$sermon->subtitle : '')
                    .'" ('.$sermon->reference.')');
                $list[$service->dateTime()->format('YmdHi')] = $sermon;
            }

        }
        ksort($list);

        return $list;
    }

    /**
     * Gibt den vollständigen Titel der Predigt zurück.
     *
     * @return string Vollständiger Titel einschließlich Untertitel.
     */
    public function getFullTitleAttribute(): string
    {
        return $this->title.($this->subtitle ? ': '.$this->subtitle : '');
    }

    /**
     * Gibt den letzten Gottesdienst dieser Predigt zurück.
     *
     * @return string|null Datum des letzten Gottesdienstes oder null, wenn keiner vorliegt.
     */
    public function latestService(): ?string
    {
        /** @var Collection<int, Carbon|string|null> $dates Termine der zugehörigen Gottesdienste. */
        $dates = $this->services->pluck('dateTime');
        $dates = $dates->reject(function ($item) {
            return (new Carbon($item)) > Carbon::now();
        })->all();
        usort($dates, function ($a, $b) {
            return ((new Carbon($a)) < (new Carbon($b))) ? 1 : -1;
        });
        if (! isset($dates[0])) {
            return null;
        }

        return (string) $dates[0];
    }

    /**
     * Setzt den Titel und erzeugt daraus einen eindeutigen Slug.
     *
     * @param  string|null  $title  Titel der Predigt.
     */
    public function setTitleAttribute(?string $title): void
    {
        $this->attributes['title'] = $title;

        $title = strtr($title ?? '', ['Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);

        $slug = $originalSlug = Str::slug($title);
        $index = 1;
        while (Sermon::where('slug', $slug)->where('id', '!=', $this->id)->first() !== null) {
            $slug = $originalSlug.'-'.$index;
            $index++;
        }
        $this->attributes['slug'] = $slug;
    }
}
