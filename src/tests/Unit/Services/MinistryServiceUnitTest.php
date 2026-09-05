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

use App\Services\MinistryService;
use Tests\TestCase;

class MinistryServiceUnitTest extends TestCase
{
    public function testPomaReturnsFourEntries(): void
    {
        $poma = MinistryService::POMA();
        $this->assertArrayHasKey('P', $poma);
        $this->assertArrayHasKey('O', $poma);
        $this->assertArrayHasKey('M', $poma);
        $this->assertArrayHasKey('A', $poma);
    }

    public function testTitleForP(): void
    {
        $this->assertIsString(MinistryService::title('P'));
        $this->assertNotEmpty(MinistryService::title('P'));
    }

    public function testTitleForO(): void
    {
        $this->assertIsString(MinistryService::title('O'));
    }

    public function testTitleForM(): void
    {
        $this->assertIsString(MinistryService::title('M'));
    }

    public function testTitleForA(): void
    {
        $this->assertEquals('Weitere Beteiligte', MinistryService::title('A'));
    }

    public function testTitlePassthroughForCustomMinistry(): void
    {
        $this->assertEquals('Lektor', MinistryService::title('Lektor'));
    }

    public function testPomaValuesAreStrings(): void
    {
        foreach (MinistryService::POMA() as $value) {
            $this->assertIsString($value);
        }
    }
}
