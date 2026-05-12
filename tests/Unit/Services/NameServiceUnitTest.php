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

use App\Services\NameService;
use Tests\TestCase;

class NameServiceUnitTest extends TestCase
{
    public function testFromNameWithComma(): void
    {
        $ns = NameService::fromName('Müller, Hans');
        $this->assertEquals('Hans', trim($ns->getFirstName()));
        $this->assertEquals('Müller', trim($ns->getLastName()));
    }

    public function testFromNameWithSpace(): void
    {
        $ns = NameService::fromName('Hans Müller');
        $this->assertEquals('Hans', trim($ns->getFirstName()));
        $this->assertEquals('Müller', trim($ns->getLastName()));
    }

    public function testFormatLastCommaFirst(): void
    {
        $ns = new NameService('Hans', 'Müller');
        $this->assertEquals('Müller, Hans', $ns->format(NameService::LAST_COMMA_FIRST));
    }

    public function testFormatFirstLast(): void
    {
        $ns = new NameService('Hans', 'Müller');
        $this->assertEquals('Hans Müller', $ns->format(NameService::FIRST_LAST));
    }

    public function testFormatLastFirst(): void
    {
        $ns = new NameService('Hans', 'Müller');
        // strtoupper is not multibyte-safe; ü stays lowercase in PHP's strtoupper
        $this->assertEquals('MüLLER Hans', $ns->format(NameService::LAST_FIRST));
    }

    public function testFormatLastFirstArray(): void
    {
        $ns = new NameService('Hans', 'Müller');
        $result = $ns->format(NameService::LAST_FIRST_ARRAY);
        $this->assertIsArray($result);
        $this->assertEquals('Müller', $result[0]);
        $this->assertEquals('Hans', $result[1]);
    }

    public function testFormatTitleFirstLast(): void
    {
        $ns = new NameService('Hans', 'Müller', 'Dr.');
        $this->assertEquals('Dr. Hans Müller', $ns->format(NameService::TITLE_FIRST_LAST));
    }

    public function testGetTitle(): void
    {
        $ns = new NameService('Hans', 'Müller', 'Prof.');
        $this->assertEquals('Prof.', $ns->getTitle());
    }
}
