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

use App\Services\FileNameService;
use Carbon\Carbon;
use Tests\TestCase;

class FileNameServiceUnitTest extends TestCase
{
    public function testMakeReturnsStringWithTitle(): void
    {
        $result = FileNameService::make('Gottesdienst');
        $this->assertIsString($result);
        $this->assertStringContainsString('Gottesdienst', $result);
    }

    public function testMakeAppendsExtensionWithDot(): void
    {
        $result = FileNameService::make('Gottesdienst', 'pdf');
        $this->assertStringEndsWith('.pdf', $result);
    }

    public function testMakeAppendsExtensionWithExistingDot(): void
    {
        $result = FileNameService::make('Gottesdienst', '.docx');
        $this->assertStringEndsWith('.docx', $result);
    }

    public function testMakeIncludesFormattedDate(): void
    {
        $date = Carbon::create(2025, 12, 24);
        $result = FileNameService::make('Gottesdienst', '', '', $date);
        $this->assertStringContainsString('20251224', $result);
    }

    public function testMakeIncludesCallSign(): void
    {
        // CallSign requires a date to avoid undefined variable in service
        $date = Carbon::create(2025, 12, 24);
        $result = FileNameService::make('Gottesdienst', '', 'KG', $date);
        $this->assertStringStartsWith('KG', $result);
    }

    public function testMakeReplacesSlashWithComma(): void
    {
        $result = FileNameService::make('Wort/Taufe');
        $this->assertStringNotContainsString('/', $result);
        $this->assertStringContainsString(',', $result);
    }

    public function testMakeAcceptsDateArray(): void
    {
        $dates = [Carbon::create(2025, 12, 24), Carbon::create(2025, 12, 25)];
        $result = FileNameService::make('Gottesdienst', '', '', $dates);
        $this->assertStringContainsString('20251224', $result);
        $this->assertStringContainsString('20251225', $result);
    }

    public function testMakeWithForcedDateFormat(): void
    {
        $date = Carbon::create(2025, 12, 24);
        $result = FileNameService::make('Test', '', '', $date, false, null, 'Y');
        $this->assertStringContainsString('2025', $result);
    }
}
