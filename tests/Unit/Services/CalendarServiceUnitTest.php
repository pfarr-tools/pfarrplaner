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

use App\Services\CalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarServiceUnitTest extends TestCase
{
    use RefreshDatabase;

    public function testInitializeMonthReturnsSundaysOnly(): void
    {
        $days = CalendarService::initializeMonth(2025, 1);
        $this->assertNotEmpty($days);
        foreach ($days as $day) {
            // date is cast as datetime, returns Carbon directly
            $this->assertEquals(0, $day->date->dayOfWeek, "Expected Sunday, got " . $day->date->format('l'));
        }
    }

    public function testInitializeMonthCountIsCorrect(): void
    {
        // January 2025 has 4 Sundays (5, 12, 19, 26)
        $days = CalendarService::initializeMonth(2025, 1);
        $this->assertCount(4, $days);
    }

    public function testInitializeMonthReturnsCollection(): void
    {
        $days = CalendarService::initializeMonth(2025, 6);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $days);
    }

    public function testInitializeMonthPersistsDays(): void
    {
        CalendarService::initializeMonth(2025, 1);
        $this->assertDatabaseCount('days', 4);
    }
}
