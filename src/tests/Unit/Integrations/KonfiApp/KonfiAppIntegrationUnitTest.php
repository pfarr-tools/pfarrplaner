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
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ReflectionProperty;
use Tests\TestCase;

class KonfiAppIntegrationUnitTest extends TestCase
{
    public function test_api_client_uses_ipv4_for_container_networking(): void
    {
        $integration = new KonfiAppIntegration('test-token');
        $client = (new ReflectionProperty(KonfiAppIntegration::class, 'client'))->getValue($integration);

        $this->assertSame('v4', $client->getConfig('force_ip_resolve'));
    }

    public function test_event_types_are_cached_after_a_successful_request(): void
    {
        Cache::setDefaultDriver('array');
        $history = [];
        $handler = new MockHandler([
            new Response(200, [], json_encode(['data' => ['veranstaltungen' => [['id' => 7]]]])),
        ]);
        $stack = HandlerStack::create($handler);
        $stack->push(Middleware::history($history));
        $integration = new KonfiAppIntegration('cache-token');
        $integration->setClient(new Client(['handler' => $stack]));

        $first = $integration->listEventTypes();
        $second = $integration->listEventTypes();

        $this->assertJsonStringEqualsJsonString($first->toJson(), $second->toJson());
        $this->assertCount(1, $history);
    }

    public function test_stale_event_types_are_used_when_the_api_is_unavailable(): void
    {
        $token = 'stale-token';
        Cache::put(
            'konfiapp-event-types-'.hash('sha256', $token).':stale',
            [['id' => 9]],
            3600
        );
        $integration = $this->integrationWithHandler($token, new MockHandler([
            new Response(503),
        ]));

        $eventTypes = $integration->listEventTypes();

        $this->assertSame([['id' => 9]], $eventTypes->toArray());
    }

    public function test_unavailable_event_types_return_an_empty_collection_without_logging_the_token(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'KonfiApp: Anfrage fehlgeschlagen'
                && ! isset($context['token'])
                && ! isset($context['request'])
            );
        $integration = $this->integrationWithHandler('secret-token', new MockHandler([
            new Response(503),
        ]));

        $eventTypes = $integration->listEventTypes();

        $this->assertTrue($eventTypes->isEmpty());
    }

    public function test_qr_codes_are_read_from_the_detail_response_for_the_requested_event_type(): void
    {
        $history = [];
        $handler = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 200,
                'shortcode' => 'ok',
                'detail' => [['code' => 'actual-code']],
            ])),
        ]);
        $stack = HandlerStack::create($handler);
        $stack->push(Middleware::history($history));
        $integration = new KonfiAppIntegration('qr-token');
        $integration->setClient(new Client(['handler' => $stack]));

        $qrCodes = $integration->listQRCodes(37578);

        $this->assertInstanceOf(Collection::class, $qrCodes);
        $this->assertJsonStringEqualsJsonString(
            json_encode([['code' => 'actual-code']]),
            $qrCodes->toJson()
        );
        $this->assertSame(
            'veranstaltungID=37578',
            $history[0]['request']->getUri()->getQuery()
        );
    }

    /**
     * Build an integration with a deterministic HTTP handler.
     */
    private function integrationWithHandler(string $token, MockHandler $handler): KonfiAppIntegration
    {
        $integration = new KonfiAppIntegration($token);
        $integration->setClient(new Client(['handler' => HandlerStack::create($handler)]));

        return $integration;
    }
}
