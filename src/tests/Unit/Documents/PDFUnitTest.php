<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 */

namespace Tests\Unit\Documents;

use App\Documents\PDF;
use ReflectionProperty;
use Tests\TestCase;

class PDFUnitTest extends TestCase
{
    public function testPdfBrowserUsesChromiumSandboxArgumentsForRootContainers(): void
    {
        $pdf = PDF::html('<html><body>Test</body></html>');
        $noSandbox = (new ReflectionProperty(PDF::class, 'noSandbox'))->getValue($pdf);

        $this->assertTrue($noSandbox);
    }
}
