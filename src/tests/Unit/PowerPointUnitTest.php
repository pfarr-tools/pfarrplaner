<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id: 6800623ca2bcf994dd408e79878da4a623c45fa8 $
 */

namespace Tests\Unit;

use App\FileFormats\PowerPoint;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class PowerPointUnitTest extends TestCase
{
    /**
     * PowerPoint image parts use the detected format and keep their relationships and macros intact.
     */
    public function test_image_parts_are_renamed_to_their_actual_format(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pptx-image-test-');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString(
            '[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="" ContentType="image/jpeg"/>'
            .'<Default Extension="jpg" ContentType="image/png"/>'
            .'<Default Extension="png" ContentType="image/png"/>'
            .'</Types>'
        );
        $zip->addFromString(
            'ppt/slides/_rels/slide1.xml.rels',
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Target="../media/no-extension." Type="image"/>'
            .'<Relationship Id="rId2" Target="../media/wrong-extension.png" Type="image"/>'
            .'</Relationships>'
        );
        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABAf/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPxB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPxB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxB//9oACAEBAAE/EB//2Q==', true);
        $zip->addFromString('ppt/media/no-extension.', $jpeg);
        $zip->addFromString('ppt/media/wrong-extension.png', $jpeg);
        $zip->addFromString('ppt/vbaProject.bin', 'macro-project');
        $zip->close();

        PowerPoint::fromFile($tempFile)->applyImageFileTypeFix();

        $zip->open($tempFile);
        $contentTypes = $zip->getFromName('[Content_Types].xml');
        $relationships = $zip->getFromName('ppt/slides/_rels/slide1.xml.rels');
        $macroProject = $zip->getFromName('ppt/vbaProject.bin');
        $hasCorrectlyNamedImages = $zip->locateName('ppt/media/no-extension.jpg') !== false
            && $zip->locateName('ppt/media/wrong-extension.jpg') !== false;
        $hasInvalidImageNames = $zip->locateName('ppt/media/no-extension.') !== false
            || $zip->locateName('ppt/media/wrong-extension.png') !== false;
        $zip->close();
        unlink($tempFile);

        $this->assertTrue($hasCorrectlyNamedImages);
        $this->assertFalse($hasInvalidImageNames);
        $this->assertStringNotContainsString('Extension=""', $contentTypes);
        $this->assertStringContainsString('Extension="jpg" ContentType="image/jpeg"', $contentTypes);
        $this->assertStringContainsString('Target="../media/no-extension.jpg"', $relationships);
        $this->assertStringContainsString('Target="../media/wrong-extension.jpg"', $relationships);
        $this->assertSame('macro-project', $macroProject);
    }

    public function test_text_entities_are_decoded_in_slide_text(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pptx-test-');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString(
            'ppt/slides/slide1.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<p:sld xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            .'<p:cSld><p:spTree><p:sp><p:txBody><a:p><a:r><a:t>Psalm &#8211; &amp;Auml;hre &#169;</a:t></a:r></a:p></p:txBody></p:sp></p:spTree></p:cSld>'
            .'</p:sld>'
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
