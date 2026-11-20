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

namespace Tests\Unit\Events;

use App\Events\ServiceUpdated;
use App\Models\Service;
use App\Models\Streaming\Broadcast;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Covers optional YouTube broadcasts during service updates.
 */
class ServiceUpdatedUnitTest extends TestCase
{
    /**
     * A missing YouTube broadcast does not interrupt the service update event.
     */
    public function test_missing_broadcast_is_ignored(): void
    {
        $event = new ServiceUpdated(new Service, collect());
        $method = new ReflectionMethod($event, 'updateBroadcastIfAvailable');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($event, null));
    }

    /**
     * An available YouTube broadcast is still updated.
     */
    public function test_available_broadcast_is_updated(): void
    {
        $event = new ServiceUpdated(new Service, collect());
        $broadcast = $this->createMock(Broadcast::class);
        $broadcast->expects($this->once())->method('update');
        $method = new ReflectionMethod($event, 'updateBroadcastIfAvailable');
        $method->setAccessible(true);

        $method->invoke($event, $broadcast);
    }
}
