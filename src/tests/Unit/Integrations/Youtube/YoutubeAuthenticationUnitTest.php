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
 */

namespace Tests\Unit\Integrations\Youtube;

use App\Integrations\Youtube\YoutubeIntegration;
use App\Models\Places\City;
use App\Models\Streaming\Broadcast;
use Google_Client;
use ReflectionClass;
use Tests\TestCase;

/**
 * Verifies that YouTube authentication refreshes raw database tokens safely.
 */
class YoutubeAuthenticationUnitTest extends TestCase
{
    /**
     * Refreshing a city's YouTube token does not parse its stored raw access token as JSON.
     */
    public function test_youtube_integration_refreshes_without_loading_raw_access_token(): void
    {
        $city = $this->cityWithRawAccessToken();
        $client = $this->createMock(Google_Client::class);
        $client->expects($this->once())
            ->method('refreshToken')
            ->with('refresh-token');
        $client->expects($this->never())->method('setAccessToken');

        $integration = (new ReflectionClass(YoutubeIntegration::class))->newInstanceWithoutConstructor();
        $integration->setClient($client);

        $integration->authenticate($city);
    }

    /**
     * Refreshing a broadcast's YouTube token does not parse its stored raw access token as JSON.
     */
    public function test_broadcast_refreshes_without_loading_raw_access_token(): void
    {
        $city = $this->cityWithRawAccessToken();
        $client = $this->createMock(Google_Client::class);
        $client->expects($this->once())
            ->method('refreshToken')
            ->with('refresh-token');
        $client->expects($this->never())->method('setAccessToken');

        $broadcast = (new ReflectionClass(Broadcast::class))->newInstanceWithoutConstructor();
        $broadcast->setClient($client);

        $broadcast->authenticate($city);
    }

    /**
     * Create a city whose access token matches the production database storage format.
     */
    private function cityWithRawAccessToken(): City
    {
        $city = new City;
        $city->google_access_token = 'raw-access-token';
        $city->google_refresh_token = 'refresh-token';

        return $city;
    }
}
