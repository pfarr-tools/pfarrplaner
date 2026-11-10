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

namespace Tests\Unit;

use App\FileFormats\ODP;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class ODPUnitTest extends TestCase
{
    public function test_text_entities_are_decoded_in_content_xml(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'odp-test-');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">'
            .'<office:body><office:presentation><text:p>Psalm &#8211; &amp;Auml;hre &#169;</text:p></office:presentation></office:body>'
            .'</office:document-content>'
        );
        $zip->close();

        $odp = ODP::fromFile($tempFile);
        $odp->applyTextEntityDecodingFix();

        $zip->open($tempFile);
        $contentXml = $zip->getFromName('content.xml');
        $zip->close();

        unlink($tempFile);

        $this->assertStringContainsString('Psalm – Ähre ©', $contentXml);
        $this->assertStringNotContainsString('&#8211;', $contentXml);
        $this->assertStringNotContainsString('&amp;Auml;', $contentXml);
        $this->assertStringNotContainsString('&#169;', $contentXml);
    }

    public function test_title_slide_transparency_is_written_to_graphic_style(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'odp-transparency-test-');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<office:document-content '
            .'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            .'xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" '
            .'xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" '
            .'xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0">'
            .'<office:automatic-styles>'
            .'<style:style style:name="gr1" style:family="graphic">'
            .'<style:graphic-properties draw:fill="solid" draw:fill-color="#FFFFFF"/>'
            .'</style:style>'
            .'</office:automatic-styles>'
            .'<office:body><office:presentation>'
            .'<draw:page><draw:frame draw:style-name="gr1" svg:y="10.610cm"/>'
            .'</draw:page></office:presentation></office:body>'
            .'</office:document-content>'
        );
        $zip->close();

        $odp = ODP::fromFile($tempFile);
        $odp->applyTitleSlideTransparencyFix();

        $zip->open($tempFile);
        $contentXml = $zip->getFromName('content.xml');
        $zip->close();

        unlink($tempFile);

        $this->assertStringContainsString('draw:opacity="85%"', $contentXml);
    }
}
