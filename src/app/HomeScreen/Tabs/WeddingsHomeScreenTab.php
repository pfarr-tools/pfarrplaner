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

namespace App\HomeScreen\Tabs;

use App\Models\Rites\Wedding;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class WeddingsHomeScreenTab extends AbstractHomeScreenTab
{
    protected $title = 'Trauungen';

    protected $description = 'Zeigt die anstehenden Trauungen';

    /**
     * @var Builder<Wedding>
     */
    protected $query = null;

    /**
     * @param  array<string, mixed>  $config  Tab configuration.\n     * @return void
     */
    public function __construct($config = [])
    {
        $this->setDefaultConfig($config, ['mine' => 0, 'newestFirst' => 0, 'excludeProcessed' => 0]);
        parent::__construct($config);
        $this->query = $this->buildQuery();
    }

    public function getTitle(): string
    {
        if ($this->config['mine']) {
            return 'Meine Trauungen';
        }

        return parent::getTitle();
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->query->count();
    }

    /**
     * @param  array<string, mixed>  $data  View data.\n     * @return mixed
     */
    public function getContent($data = [])
    {
        $data['weddings'] = $this->query->get();

        return parent::getContent($data);
    }

    /**
     * @param  array<string, mixed>  $data  Additional data.\n     * @return array<string, mixed>
     */
    public function toArray($data = [])
    {
        $data['weddings'] = $this->query->get();
        $data['count'] = count($data['weddings']);

        return parent::toArray($data);
    }

    /**
     * Build the query
     *
     * @return Builder<Wedding>
     */
    public function buildQuery()
    {
        $start = Carbon::now()->setTime(0, 0, 0);
        $end = Carbon::now()->addMonths(2);

        $order = $this->config['newestFirst'] ? 'desc' : 'asc';

        $query = Wedding::with(['service'])
            ->select('weddings.*')
            ->join('services', 'weddings.service_id', 'services.id')
            ->whereHas('service', function ($service) {
                if (! $this->config['excludeProcessed']) {
                    $service->startingFrom(Carbon::now()->subWeeks(2));
                } else {
                    $service->where(function ($q) {
                        $q->startingFrom(Carbon::now());
                        $q->orWhere('weddings.processed', '!=', 1);
                    });
                }
                if ($this->config['mine']) {
                    $service->whereHas(
                        'participants',
                        function ($query) {
                            $query->where('user_id', $this->user()->id);
                        }
                    );
                } else {
                    $service->inCities($this->user()->writableCities->pluck('id'));
                }
            })->orderBy('services.date', $order);

        if ($this->config['excludeProcessed']) {
            $query->where('processed', '!=', 1);
        }

        return $query;
    }
}
