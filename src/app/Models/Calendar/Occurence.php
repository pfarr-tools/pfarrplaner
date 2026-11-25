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

namespace App\Models\Calendar;

use App\Models\Ads\AdConfig;
use App\Models\Scopes\ServicesOnlyScope;
use App\Models\Service;
use App\Services\LiturgyService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Occurence extends Model
{
    protected $guarded = [];

    protected $casts = ['start' => 'datetime', 'end' => 'datetime'];

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id')->withoutGlobalScope(ServicesOnlyScope::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function event()
    {
        return $this->belongsTo(Service::class, 'service_id')->withoutGlobalScope(ServicesOnlyScope::class);
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeBetween(Builder $query, Carbon $start, Carbon $end)
    {
        return $query->where('start', '<=', $end)
            ->where('end', '>=', $start);
    }

    /**
     * Find occurences which have a specific ad running at a specific date
     *
     * @param  string  $adChannelSlug  Slug of the ad channel to be used
     * @param  Carbon  $adDate  Date of the ad
     * @param  bool  $exactDate  If true, the ad date must match exactly, otherwise the ad date is used as lower bound
     * @param  bool  $includeEnded  If true, ended occurences are included
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeAdRunningAt(Builder $query, string $adChannelSlug, Carbon $adDate, bool $exactDate = false, bool $includeEnded = false)
    {
        if (! $includeEnded) {
            $query->where('end', '>=', $adDate);
        }
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

    /**
     * Determine whether an ad for the given channel is running at the given date
     *
     * @param  string  $adChannelSlug  Slug of the ad channel to be used
     * @param  Carbon  $adDate  Date of the ad
     * @param  bool  $exactDate  If true, the ad date must match exactly, otherwise the ad date is used as lower bound
     * @return bool Whether the ad is running at the given date.
     */
    public function hasAdRunningAt(string $adChannelSlug, Carbon $adDate, bool $exactDate = false): bool
    {
        $adConfig = $this->getAdConfig($adChannelSlug);
        if ($adConfig === null) {
            return false;
        }
        $adStart = $this->start->copy()->subDays($adConfig->offset);

        return $exactDate ? $adStart->toDateString() == $adDate->toDateString() : $adStart <= $adDate;
    }

    /**
     * @param  Builder<static>  $query  Query being filtered.
     * @return Builder<static>
     */
    public function scopeStartingFrom(Builder $query, Carbon $start)
    {
        return $query->where('end', '>=', $start);
    }

    /**
     * Add liturgical info attribute
     *
     * @return array<string, mixed> Liturgical information for the occurence.
     */
    public function getLiturgicalInfoAttribute(): array
    {
        if (! $this->event) {
            return [];
        }
        if ($this->event->isAlternateProprium) {
            return $this->event->liturgicalInfo;
        }
        $info = LiturgyService::getLiturgyInfoByDate($this->start);
        if (! is_array($info)) {
            return [];
        }
        /** @var array<string, mixed> $first */
        $first = $info[0] ?? [];

        return is_array($first) ? $first : [];
    }

    /**
     * Get the ad config for a specific ad channel
     */
    public function getAdConfig(string $adChannelKey): ?AdConfig
    {
        return $this->event?->adConfigs->firstWhere('slug', $adChannelKey);
    }

    /**
     * Get the ad text for a specific ad channel
     *
     * @param  string  $defaultTo  Default text to be used if no ad text is configured
     */
    public function getAdText(string $adChannelKey, string $defaultTo = ''): string
    {
        $adConfig = $this->getAdConfig($adChannelKey);
        if ($adConfig) {
            $adText = $adConfig->ad_text ?? '';
        }

        $service = $this->service;
        $serviceText = $service === null ? '' : ($service->ad_text ?: $service->descriptionText());

        return ($adText ?? '') ?: $defaultTo ?: $serviceText;
    }
}
