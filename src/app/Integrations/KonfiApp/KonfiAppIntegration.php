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

namespace App\Integrations\KonfiApp;


use App\Integrations\AbstractIntegration;
use App\Models\Places\City;
use App\Models\Service;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Throwable;

/**
 * Class KonfiAppIntegration
 * @package App\Integrations\KonfiApp
 */
class KonfiAppIntegration extends AbstractIntegration
{

    /**
     *
     */
    protected const API_URL = 'https://api.konfiapp.de/v2/';

    /**
     * @var string
     */
    protected $apiKey = '';

    /** @var Client */
    protected $client;

    /** @var array last request */
    protected $lastRequest = [];

    /** @var string last endpoint */
    protected $lastEndpoint = '';

    /** @var string  last request type */
    protected $lastRequestType = '';

    /**
     * KonfiAppIntegration constructor.
     * @param $apiKey
     */
    public function __construct($apiKey)
    {
        $this->setApiKey($apiKey);
        $this->setClient(new Client(['base_uri' => self::API_URL]));
    }

    /**
     * Get an instance of this integration for a particular city
     * @param City $city
     * @return KonfiAppIntegration
     */
    public static function get(City $city): KonfiAppIntegration
    {
        return (new self($city->konfiapp_apikey));
    }

    /**
     * Returns true if the integration is active and properly configured to work for a specific city
     *
     * For this integration, this is the case if the konfiapp_apikey is present.
     *
     * @param City $city
     * @return bool
     */
    public static function isActive(City $city): bool
    {
        return ($city->konfiapp_apikey != '');
    }

    /**
     * Get a collection with all defined event types from KonfiApp
     *
     * @return Collection Event types
     * @throws Exception when request status is not 200
     */
    public function listEventTypes()
    {
        $response = $this->requestData('verwaltung/veranstaltungen/');
        return $response ? collect($response->data->veranstaltungen) : collect();
    }

    /**
     * Send a request to the public API for KonfiApp and return the contents of the response's data field
     *
     * @param $requestType
     * @param $path
     * @param array $arguments
     * @param String $requestType
     * @return mixed Response data field
     * @throws Exception
     */
    protected function requestData($path, $arguments = [], $requestType = 'GET'): stdClass|bool
    {
        $response = $this->request($requestType, $path, $arguments);
        if ((!$response) || ($response->getStatusCode() != 200)) {
            return false;
        }
        return json_decode((string)$response->getBody());
    }

    /**
     * Send a request to the public API for KonfiApp
     *
     * This will automatically add the api key
     *
     * @param $requestType
     * @param $path
     * @param array $arguments
     * @return ResponseInterface|bool
     */
    protected function request($requestType, $path, $arguments = []): ResponseInterface|bool
    {
        $this->lastRequest = [
            'headers' => ['X-Konfiapp-Token' => $this->apiKey]
        ];
        if (strtoupper($requestType) === 'GET') {
            $this->lastRequest['query'] = $arguments;
        } else {
            $this->lastRequest['json'] = $arguments;
            $this->lastRequest['headers']['Accept'] = 'application/json';
            $this->lastRequest['headers']['Content-Type'] = 'application/json';
        }
        $this->lastEndpoint = static::API_URL.$path;
        $this->lastRequestType = $requestType;

        try {
            $response = $this->client->request(
                $requestType,
                $path,
                $this->lastRequest,
            );
        } catch (Throwable $exception) {
            Log::warning('KonfiApp: Anfrage fehlgeschlagen', [
                'requestType' => $this->lastRequestType,
                'endpoint' => $this->lastEndpoint,
                'token' => $this->apiKey,
                'request' => $this->lastRequest,
                'message' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            return false;
        }

        if ((!$response) || $response->getStatusCode() != 200 || (!isset(json_decode($response->getBody(), true)['data']))) {
            Log::debug('KonfiApp: KonfiApp returns error response', [
                'requestType' => $this->lastRequestType,
                'endpoint' => $this->lastEndpoint,
                'token' => $this->apiKey,
                'request' => $this->lastRequest,
                'response' => $response ? json_decode($response->getBody(), true) : 'false',
            ]);
            return false;
        }

        return $response;
    }

    /**
     * Handle service update
     *
     * This will create a new qr code if the service does not have one yet
     *
     * @param Service $service
     * @throws Exception
     */
    public function handleServiceUpdate(Service $service, $requestedChange)
    {
        try {
            if ($requestedChange == '') {
                return null;
            }

            if ($service->konfiapp_event_qr == '') {
                $service->konfiapp_event_type = $requestedChange;
                return $this->addQRCodeToService($service);
            }

            if (($service->konfiapp_event_type != '') && ($service->konfiapp_event_type != $requestedChange)) {
                return $this->updateServiceQRCode($service, $requestedChange);
            }
        } catch (Throwable $exception) {
            Log::warning('KonfiApp: QR-Code konnte fuer Gottesdienst nicht aktualisiert werden.', [
                'service' => $service->id,
                'requestedChange' => $requestedChange,
                'message' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
        }

        return null;
    }

    /**
     * Add a QR code to a service and save it with the service record
     * @param Service $service
     * @return Service
     * @throws Exception
     */
    public function addQRCodeToService(Service $service): Service {
        Log::debug('Updating service #'.$service->id.', no KonfiApp QR set yet.');
        $code = $this->createQRCode($service);
        if (!$code) {
            Log::warning('KonfiApp: Kein QR-Code fuer Gottesdienst erzeugt.', ['service' => $service->id]);
            return $service;
        }
        Log::debug('Got code '.$code);
        $service->update(['konfiapp_event_qr' => $code]);
        $service->refresh();
        Log::debug('Updated service to code '.$service->konfiapp_event_qr);
        return $service;
    }

    /**
     * Update a service's existing QR code
     * @param Service $service
     * @param $eventType
     * @return Service
     * @throws Exception
     */
    public function updateServiceQRCode(Service $service, $eventType = null): Service
    {
        $eventType ??= $service->konfiapp_event_type;
        // change of event type: old qr needs to be deleted first
        Log::debug('Updating service #'.$service->id.', changed KonfiApp event type from '.$service->konfiapp_event_type.' to '.$eventType);
        Log::debug('Deleting old KonfiApp QR code '.$service->konfiapp_event_qr);
        $this->deleteQRCodeByCode($service->konfiapp_event_qr, $service->konfiapp_event_type);
        $code = $this->createQRCode($service, $eventType);
        if (!$code) {
            Log::warning('KonfiApp: QR-Code fuer Gottesdienst nach Typwechsel nicht neu erzeugt.', [
                'service' => $service->id,
                'eventType' => $eventType,
            ]);
            return $service;
        }
        Log::debug('Got code '.$code);
        $service->update(
            ['konfiapp_event_type' => $eventType, 'konfiapp_event_qr' => $code]
        );
        $service2 = Service::find($service->id);
        Log::debug('Updated service to code '.$service2->konfiapp_event_qr);
        return $service2;
    }

    /**
     * Create a QR code for a service
     * @param Service $service
     * @return mixed
     * @throws Exception
     */
    public function createQRCode(Service $service, ?int $eventType = null): ?string
    {
        $start = $service->date->setTimeZone('Europe/Berlin');
        $data = [
            'veranstaltungID' => $eventType ?? $service->konfiapp_event_type,
            'dateStart' => $start->format('Y.m.d'),
            'dateEnd' => $start->format('Y.m.d'),
            'timeStart' => $start->format('H:i'),
            'timeEnd' => $start->clone()->addHour(3)->format('H:i'),
        ];

        $response = $this->requestData(
            'verwaltung/veranstaltungen/qr/', $data,
             'POST'
        );

        return $response->code ?? null;
    }

    /**
     * @param $code
     * @param $type
     * @throws Exception
     */
    public function deleteQRCodeByCode($code, $type)
    {
        $response = $this->requestData(
            'verwaltung/veranstaltungen/qr/',
            [
                'veranstaltungID' => $type,
            ]
        );
        $codes = $response->detail ?? [];

        foreach ($codes as $qrcode) {
            if ($qrcode->code == $code) {
                $this->deleteQRCode($qrcode->id);
            }
        }
    }

    /**
     * Delete a qr associated with a service
     * @param string $id Code id
     * @throws Exception
     */
    public function deleteQRCode($id)
    {
        $this->requestData(
            'verwaltung/veranstaltungen/qr/delete/',
            [
                'id' => $id,
            ]
        );
    }

    /**
     * @param Service $service
     * @return Service
     * @throws Exception
     */
    public function handleServiceDelete(Service $service)
    {
        if ($service->konfiapp_event_qr != '') {
            $this->deleteQRCode($service->konfiapp_event_qr);
        }
        $service->update(['konfiapp_event_qr' => '']);
        return $service;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function listQRCodes()
    {
        return $this->requestData('verwaltung/veranstaltungen/qr/list/', ['veranstaltungID' => 682])->detail;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }


}
