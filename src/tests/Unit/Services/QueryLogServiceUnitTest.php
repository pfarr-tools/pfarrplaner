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

use App\Services\QueryLogService;
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

    public function testFileReturnsStoragePath(): void
    {
        $path = QueryLogService::file();
        $this->assertStringContainsString('query.log', $path);
    }

    public function testAllReturnsEmptyArrayWhenNoEntries(): void
    {
        File::delete(QueryLogService::file());
        $result = QueryLogService::all();
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testPutAndAllRoundtrip(): void
    {
        chmod(QueryLogService::file(), 0644);
        QueryLogService::put('INSERT INTO test VALUES (1)');
        $entries = QueryLogService::all();
        $this->assertCount(1, $entries);
        $this->assertEquals('INSERT INTO test VALUES (1)', $entries[0]['query']);
        $this->assertSame(0666, fileperms(QueryLogService::file()) & 0777);
    }

    public function testClearEmptiesLog(): void
    {
        QueryLogService::put('INSERT INTO test VALUES (2)');
        chmod(QueryLogService::file(), 0644);
        QueryLogService::clear();
        $entries = QueryLogService::all();
        $this->assertCount(0, $entries);
        $this->assertSame(0666, fileperms(QueryLogService::file()) & 0777);
    }

    public function testDateReturnsCarbonInstance(): void
    {
        QueryLogService::clear();
        $date = QueryLogService::date();
        $this->assertInstanceOf(\Carbon\Carbon::class, $date);
    }
}
