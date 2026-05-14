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
 */

namespace Tests\Unit;

use App\FileFormats\PowerPoint;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class PowerPointUnitTest extends TestCase
{
    /**
     * @return void
     */
    public function testTextEntitiesAreDecodedInSlideText(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pptx-test-');
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString(
            'ppt/slides/slide1.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<p:sld xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            . '<p:cSld><p:spTree><p:sp><p:txBody><a:p><a:r><a:t>Psalm &#8211; &amp;Auml;hre &#169;</a:t></a:r></a:p></p:txBody></p:sp></p:spTree></p:cSld>'
            . '</p:sld>'
        );
        $zip->close();

        $powerPoint = PowerPoint::fromFile($tempFile);
        $powerPoint->applyTextEntityDecodingFix();

        $zip->open($tempFile);
        $slideXml = $zip->getFromName('ppt/slides/slide1.xml');
        $zip->close();

        unlink($tempFile);

        $this->assertStringContainsString('Psalm – Ähre ©', $slideXml);
        $this->assertStringNotContainsString('&#8211;', $slideXml);
        $this->assertStringNotContainsString('&amp;Auml;', $slideXml);
        $this->assertStringNotContainsString('&#169;', $slideXml);
    }
}
