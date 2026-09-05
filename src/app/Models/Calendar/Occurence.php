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
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
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

namespace App\Models\Calendar;

use App\Models\Ads\AdConfig;
use App\Models\Scopes\ServicesOnlyScope;
use App\Models\Service;
use App\Services\LiturgyService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Occurence extends Model
{

    protected $guarded = [];
    protected $casts = ['start' => 'datetime', 'end' => 'datetime'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id')->withoutGlobalScope(ServicesOnlyScope::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event()
    {
        return $this->belongsTo(Service::class, 'service_id')->withoutGlobalScope(ServicesOnlyScope::class);
    }

    /**
     * @param Builder $query
     * @param Carbon $start
     * @param Carbon $end
     * @return Builder
     */
    public function scopeBetween(Builder $query, Carbon $start, Carbon $end)
    {
        return $query->where('start', '<=', $end)
            ->where('end', '>=', $start);
    }

    /**
     * Find occurences which have a specific ad running at a specific date
     *
     * @param Builder $query
     * @param string $adChannelSlug Slug of the ad channel to be used
     * @param Carbon $adDate Date of the ad
     * @param bool $exactDate If true, the ad date must match exactly, otherwise the ad date is used as lower bound
     * @param bool $includeEnded If true, ended occurences are included
     * @return Builder
     */
    public function scopeAdRunningAt(Builder $query, string $adChannelSlug, Carbon $adDate, bool $exactDate = false, bool $includeEnded = false)
    {
        if (!$includeEnded) $query->where('end', '>=', $adDate);
        $query->whereHas('service', function ($query2) use ($adChannelSlug, $adDate, $exactDate) {
            $query2->whereHas('adConfigs', function ($query3) use ($adChannelSlug, $adDate, $exactDate) {
                $query3->where('ad_configs.slug', $adChannelSlug)
                     ->whereRaw('DATE_SUB(DATE(services.date), INTERVAL ad_configs.offset DAY) '
                                .($exactDate ? '=' : '<=').' ?',
                                [$adDate->toDateString()]
                        );
                });
        });
        return $query;
    }

    public function hasAdRunningAt($adChannelSlug, Carbon $adDate, bool $exactDate = false)
    {
        $adConfig = $this->getAdConfig($adChannelSlug);
        $adStart = $this->start->copy()->subDays($adConfig->offset);
        return $exactDate ? $adStart->toDateString() == $adDate->toDateString() : $adStart <= $adDate;
    }

    /**
     * @param Builder $query
     * @param Carbon $start
     * @return Builder
     */
    public function scopeStartingFrom(Builder $query, Carbon $start)
    {
        return $query->where('end', '>=', $start);
    }

    public function getLiturgicalInfoAttribute()
    {
        if (!$this->event) return [];
        if ($this->event->isAlternateProprium) return $this->event->liturgicalInfo;
        $info = LiturgyService::getLiturgyInfoByDate($this->start);
        return $info[0] ?? [];
    }


    /**
     * Get the ad config for a specific ad channel
     * @param string $adChannelKey
     * @return AdConfig|null
     */
    public function getAdConfig(string $adChannelKey): AdConfig|null
    {
        return $this->event->adConfigs->firstWhere('slug', $adChannelKey);
    }

    /**
     * Get the ad text for a specific ad channel
     * @param string $adChannelKey
     * @param string $defaultTo Default text to be used if no ad text is configured
     * @return string
     */
    public function getAdText(string $adChannelKey, string $defaultTo = ''): string
    {
        $adConfig = $this->getAdConfig($adChannelKey);
        if ($adConfig) {
            $adText = $adConfig->ad_text ?? '';
        }
        return ($adText ?? '') ?: $defaultTo ?: $this->service->ad_text ?: $this->service->descriptionText();
    }
}
