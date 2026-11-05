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

namespace Tests\Unit\Services;

use App\Services\MinistryService;
use Tests\TestCase;

class MinistryServiceUnitTest extends TestCase
{
    public function test_poma_returns_four_entries(): void
    {
        $poma = MinistryService::POMA();
        $this->assertArrayHasKey('P', $poma);
        $this->assertArrayHasKey('O', $poma);
        $this->assertArrayHasKey('M', $poma);
        $this->assertArrayHasKey('A', $poma);
    }

    public function test_title_for_p(): void
    {
        $this->assertIsString(MinistryService::title('P'));
        $this->assertNotEmpty(MinistryService::title('P'));
    }

    public function test_title_for_o(): void
    {
        $this->assertIsString(MinistryService::title('O'));
    }

    public function test_title_for_m(): void
    {
        $this->assertIsString(MinistryService::title('M'));
    }

    public function test_title_for_a(): void
    {
        $this->assertEquals('Weitere Beteiligte', MinistryService::title('A'));
    }

    public function test_title_passthrough_for_custom_ministry(): void
    {
        $this->assertEquals('Lektor', MinistryService::title('Lektor'));
    }

    public function test_poma_values_are_strings(): void
    {
        foreach (MinistryService::POMA() as $value) {
            $this->assertIsString($value);
        }
    }
}
