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

use App\FileFormats\ODP;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class ODPUnitTest extends TestCase
{
    /**
     * @return void
     */
    public function testTextEntitiesAreDecodedInContentXml(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'odp-test-');
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">'
            . '<office:body><office:presentation><text:p>Psalm &#8211; &amp;Auml;hre &#169;</text:p></office:presentation></office:body>'
            . '</office:document-content>'
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
}
