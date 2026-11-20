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

use App\Integrations\Youtube\Commands\UpdateYoutubeStream;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Covers YouTube stream updates when videos have incomplete live-stream metadata.
 */
class UpdateYoutubeStreamUnitTest extends TestCase
{
    /**
     * A video without live-streaming details is not treated as an ended stream.
     */
    public function test_video_without_live_streaming_details_has_not_ended(): void
    {
        /** @var \Google_Service_YouTube_Video&MockObject $video */
        $video = $this->createMock(\Google_Service_YouTube_Video::class);
        $video->method('getLiveStreamingDetails')->willReturn(null);

        $method = new ReflectionMethod(UpdateYoutubeStream::class, 'videoHasEnded');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(new UpdateYoutubeStream, $video));
    }
}
