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

namespace Tests\Unit\Liturgy;

use App\Liturgy\LiturgySheets\A3SongListLiturgySheet;
use Tests\TestCase;

/**
 * Verify that the A3 song list uses Puppeteer's supported landscape options.
 */
class A3SongListLiturgySheetUnitTest extends TestCase
{
    /**
     * The sheet uses A3 paper in landscape orientation.
     */
    public function test_a3_song_list_uses_supported_landscape_pdf_options(): void
    {
        $layout = (new A3SongListLiturgySheet)->getLayout();

        $this->assertSame('A3', $layout['format']);
        $this->assertTrue($layout['landscape']);
    }
}
