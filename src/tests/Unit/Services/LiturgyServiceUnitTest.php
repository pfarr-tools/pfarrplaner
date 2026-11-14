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

use App\Services\LiturgyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LiturgyServiceUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();

        $year = Carbon::today()->year;
        Storage::put('liturgy/'.$year.'.json', json_encode(['Tage' => []]));
        Storage::put('liturgy/2024.json', json_encode(['Tage' => []]));
        Storage::put('liturgy/lesejahr-2024.json', json_encode('A'));

        // Reset static caches so each test starts clean
        $calendarsRef = new \ReflectionProperty(LiturgyService::class, 'calendars');
        $calendarsRef->setAccessible(true);
        $calendarsRef->setValue(null, []);

        $lectionaryRef = new \ReflectionProperty(LiturgyService::class, 'lectionaryYears');
        $lectionaryRef->setAccessible(true);
        $lectionaryRef->setValue(null, []);

        $instanceRef = new \ReflectionProperty(LiturgyService::class, 'instance');
        $instanceRef->setAccessible(true);
        $instanceRef->setValue(null, null);
    }

    public function test_get_instance_returns_same_instance(): void
    {
        $a = LiturgyService::getInstance();
        $b = LiturgyService::getInstance();
        $this->assertSame($a, $b);
    }

    public function test_get_liturgy_info_by_date_returns_array(): void
    {
        $result = LiturgyService::getLiturgyInfoByDate(Carbon::today());
        $this->assertIsArray($result);
    }

    public function test_get_lectionary_year_returns_value(): void
    {
        $year = LiturgyService::getLectionaryYear(2024);
        $this->assertNotNull($year);
    }

    public function test_get_liturgy_by_code_returns_empty_for_unknown_code(): void
    {
        $result = LiturgyService::getLiturgyByCode(2024, 'NONEXISTENT_CODE_12345');
        $this->assertEmpty($result);
    }

    public function test_get_year_fetches_missing_calendar_from_kirchenjahr_api(): void
    {
        Http::fake([
            'https://kirchenjahr.pfarr.tools/api/jahr/2099' => Http::response([
                'Jahr' => 2099,
                'Tage' => ['2099-01-01' => [['Code' => 'TEST']]],
            ]),
        ]);

        $calendar = LiturgyService::getYear(2099);

        $this->assertSame(2099, $calendar['Jahr']);
        Storage::assertExists('liturgy/2099.json');
        Http::assertSent(fn ($request) => $request->url() === 'https://kirchenjahr.pfarr.tools/api/jahr/2099');
    }
}
