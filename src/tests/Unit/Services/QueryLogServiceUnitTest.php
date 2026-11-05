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

use App\Services\QueryLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class QueryLogServiceUnitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        QueryLogService::clear();
    }

    protected function tearDown(): void
    {
        QueryLogService::clear();
        parent::tearDown();
    }

    public function test_file_returns_storage_path(): void
    {
        $path = QueryLogService::file();
        $this->assertStringContainsString('query.log', $path);
    }

    public function test_all_returns_empty_array_when_no_entries(): void
    {
        File::delete(QueryLogService::file());
        $result = QueryLogService::all();
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_put_and_all_roundtrip(): void
    {
        QueryLogService::put('INSERT INTO test VALUES (1)');
        $entries = QueryLogService::all();
        $this->assertCount(1, $entries);
        $this->assertEquals('INSERT INTO test VALUES (1)', $entries[0]['query']);
    }

    public function test_clear_empties_log(): void
    {
        QueryLogService::put('INSERT INTO test VALUES (2)');
        QueryLogService::clear();
        $entries = QueryLogService::all();
        $this->assertCount(0, $entries);
    }

    public function test_date_returns_carbon_instance(): void
    {
        QueryLogService::clear();
        $date = QueryLogService::date();
        $this->assertInstanceOf(Carbon::class, $date);
    }
}
