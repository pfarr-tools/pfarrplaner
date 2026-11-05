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

use App\Services\FileNameService;
use Carbon\Carbon;
use Tests\TestCase;

class FileNameServiceUnitTest extends TestCase
{
    public function test_make_returns_string_with_title(): void
    {
        $result = FileNameService::make('Gottesdienst');
        $this->assertIsString($result);
        $this->assertStringContainsString('Gottesdienst', $result);
    }

    public function test_make_appends_extension_with_dot(): void
    {
        $result = FileNameService::make('Gottesdienst', 'pdf');
        $this->assertStringEndsWith('.pdf', $result);
    }

    public function test_make_appends_extension_with_existing_dot(): void
    {
        $result = FileNameService::make('Gottesdienst', '.docx');
        $this->assertStringEndsWith('.docx', $result);
    }

    public function test_make_includes_formatted_date(): void
    {
        $date = Carbon::create(2025, 12, 24);
        $result = FileNameService::make('Gottesdienst', '', '', $date);
        $this->assertStringContainsString('20251224', $result);
    }

    public function test_make_includes_call_sign(): void
    {
        // CallSign requires a date to avoid undefined variable in service
        $date = Carbon::create(2025, 12, 24);
        $result = FileNameService::make('Gottesdienst', '', 'KG', $date);
        $this->assertStringStartsWith('KG', $result);
    }

    public function test_make_replaces_slash_with_comma(): void
    {
        $result = FileNameService::make('Wort/Taufe');
        $this->assertStringNotContainsString('/', $result);
        $this->assertStringContainsString(',', $result);
    }

    public function test_make_accepts_date_array(): void
    {
        $dates = [Carbon::create(2025, 12, 24), Carbon::create(2025, 12, 25)];
        $result = FileNameService::make('Gottesdienst', '', '', $dates);
        $this->assertStringContainsString('20251224', $result);
        $this->assertStringContainsString('20251225', $result);
    }

    public function test_make_with_forced_date_format(): void
    {
        $date = Carbon::create(2025, 12, 24);
        $result = FileNameService::make('Test', '', '', $date, false, null, 'Y');
        $this->assertStringContainsString('2025', $result);
    }
}
