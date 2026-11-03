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

namespace Tests\Unit\Services;

use App\Services\QRService;
use PHPUnit\Framework\TestCase;

class QRServiceUnitTest extends TestCase
{
    public function testBuildCommandUsesKonfiAppCompatibleQrParameters(): void
    {
        $command = QRService::buildCommand('/tmp/qr output.png', '/tmp/payload file.txt');

        $this->assertStringContainsString('qrencode -8 -l M -v 2 -m 1 -o', $command);
        $this->assertStringNotContainsString(' -M ', $command);
        $this->assertStringContainsString("'/tmp/qr output.png'", $command);
        $this->assertStringEndsWith(" -r '/tmp/payload file.txt'", $command);
    }
}
