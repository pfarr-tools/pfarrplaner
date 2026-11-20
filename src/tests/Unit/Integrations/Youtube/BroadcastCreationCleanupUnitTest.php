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

use App\Models\Streaming\Broadcast;
use Google_Service_YouTube;
use Google_Service_YouTube_Resource_LiveBroadcasts;
use Google_Service_YouTube_Resource_Videos;
use Google_Service_YouTube_Video;
use ReflectionClass;
use Tests\TestCase;

/**
 * Verifies cleanup when YouTube broadcast creation only partially succeeds.
 */
class BroadcastCreationCleanupUnitTest extends TestCase
{
    /**
     * Delete the inserted broadcast when updating its video metadata fails.
     */
    public function test_it_deletes_the_broadcast_when_video_update_fails(): void
    {
        $youtube = $this->createMock(Google_Service_YouTube::class);
        $videos = $this->getMockBuilder(Google_Service_YouTube_Resource_Videos::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['update'])
            ->getMock();
        $liveBroadcasts = $this->getMockBuilder(Google_Service_YouTube_Resource_LiveBroadcasts::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['delete'])
            ->getMock();
        $video = new Google_Service_YouTube_Video;
        $originalException = new \RuntimeException('YouTube video update failed');

        $videos->expects($this->once())
            ->method('update')
            ->with('snippet,status', $video)
            ->willThrowException($originalException);
        $liveBroadcasts->expects($this->once())
            ->method('delete')
            ->with('broadcast-id');
        $youtube->videos = $videos;
        $youtube->liveBroadcasts = $liveBroadcasts;

        $broadcast = (new ReflectionClass(Broadcast::class))->newInstanceWithoutConstructor();
        $broadcast->setYoutube($youtube);

        try {
            $this->invokeVideoUpdate($broadcast, $video, 'broadcast-id');
            $this->fail('The original YouTube API error should be rethrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame($originalException, $exception);
        }
    }

    /**
     * Invoke the internal post-insert video update used by broadcast creation.
     */
    private function invokeVideoUpdate(Broadcast $broadcast, Google_Service_YouTube_Video $video, string $broadcastId): void
    {
        $method = (new ReflectionClass(Broadcast::class))->getMethod('updateBroadcastVideo');
        $method->invoke($broadcast, $video, $broadcastId);
    }
}
