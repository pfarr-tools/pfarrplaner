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

namespace App\Integrations\CommuniApp;


use App\Integrations\AbstractIntegration;
use App\Models\Calendar\Occurence;
use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Scheduling\Schedule;

class CommuniAppIntegration extends AbstractIntegration
{
    const AUTH_HEADER = 'X-Authorization';
    const COMMUNI_API_URL = 'https://api.communiapp.de';
    const ROUTE_EVENT = '/rest/event';


    /** @var Client */
    protected $client = null;

    /** @var City */
    protected $city = null;

    /**
     * Schedule the push to CommuniApp to be run daily
     * @param Schedule $schedule
     * @return void
     */
    public static function schedule(Schedule $schedule)
    {
        $schedule->command('communiapp:push')->timezone('Europe/Berlin')->dailyAt('08:00');
    }


    /**
     * Check if this integration is active for a particular city
     * @param City $city
     * @return bool
     */
    public static function isActive(City $city): bool
    {
        return ($city->communiapp_token != '') && ($city->communiapp_default_group_id !== null);
    }

    /**
     * Get an instance of this integration for a particular city
     * @param City $city
     * @return CommuniAppIntegration
     */
    public static function get(City $city): CommuniAppIntegration
    {
        return new self($city);
    }

    /**
     * CommuniAppIntegration constructor.
     * @param City $city
     */
    public function __construct(City $city)
    {
        $this->city = $city;
        $this->client = new Client(
            [
                'base_uri' => self::COMMUNI_API_URL,
                'headers' => [
                    self::AUTH_HEADER => sprintf(' Bearer %s', $city->communiapp_token),
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }

    /**
     * Create a new service on CommuniApp
     * @param Service $service
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handleServiceCreated(Service $service) {}

    /**
     * Create a new service on CommuniApp (only here for reference)
     * @param Service $service
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function createService(Service $service)
    {
        return $this->client->post(self::ROUTE_EVENT, ['body' => json_encode($this->getServiceArray($service))]);
    }

    public function publish(Occurence $event)
    {
        return $this->client->post(self::ROUTE_EVENT, ['body' => json_encode($this->getEventArray($event))]);
    }

    public function handleServiceUpdated(Service $service) {}

    /**
     * Update an existing service on CommuniApp (only here for reference)
     * @param Service $service
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function updateService(Service $service) {
        return $this->client->put(sprintf('%s/%s', self::ROUTE_EVENT, $service->communiapp_id),
                                       ['body' => json_encode($this->getServiceArray($service))]);
    }

    /**
     * Convert a service to a dataset array for CommuniApp
     * @param Service $service
     * @return array
     */
    protected function getServiceArray(Service $service): array {
        return [
            'dateTime' => $service->dateTime->setTimezone('Europe/Berlin')->format('Y-m-d H:i:s'),
            'isOfficial' => true,
            'group' => $this->city->communiapp_default_group_id,
            'title' => $service->titleText(false, true),
            'location' => $service->locationText(),
            'description' => $service->broadcast_description,
        ];
    }

    /**
     * Convert an event to a dataset array for CommuniApp
     * @param Occurence $event
     * @return array
     */
    protected function getEventArray(Occurence $event): array {
        return [
            'dateTime' => $event->start->setTimezone('Europe/Berlin')->format('Y-m-d H:i:s'),
            'isOfficial' => true,
            'group' => $this->city->communiapp_default_group_id,
            'title' => $event->service->titleText(false, true),
            'location' => $event->service->locationText(),
            'picUrl' => $event->service->getImageCutUrl('CommuniApp'),
            'description' => $event->getAdText('communiapp'),
        ];
    }



    /**
     * Delete a service from CommuniApp
     * @param Service $service
     */
    public function handleserviceDeleted(Service $service) {}

    /**
     * Delete a service from CommuniApp (only here for reference)
     * @param Service $service
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteService(Service $service)
    {
        return $this->client->delete(sprintf('%s/%s', self::ROUTE_EVENT, $service->communiapp_id));
    }

}
