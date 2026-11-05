<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 */

namespace Tests\Unit\Integrations\KonfiApp;

use App\Integrations\KonfiApp\KonfiAppIntegration;
use ReflectionProperty;
use Tests\TestCase;

class KonfiAppIntegrationUnitTest extends TestCase
{
    public function test_api_client_uses_ipv4_for_container_networking(): void
    {
        $integration = new KonfiAppIntegration('test-token');
        $client = (new ReflectionProperty(KonfiAppIntegration::class, 'client'))->getValue($integration);

        $this->assertSame(CURL_IPRESOLVE_V4, $client->getConfig('curl')[CURLOPT_IPRESOLVE]);
    }
}
