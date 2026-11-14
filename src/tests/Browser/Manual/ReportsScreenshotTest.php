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

namespace Tests\Browser\Manual;

use App\Models\Places\City;
use App\Models\Service;
use Laravel\Dusk\Browser;

class ReportsScreenshotTest extends ManualScreenshotTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $city = City::factory()->create(['name' => 'Mustergemeinde']);
        Service::factory()->create([
            'city_id' => $city->id,
            'date' => now()->subDay()->setTime(10, 30),
            'time' => '10:30:00',
            'title' => 'Gottesdienst',
            'description' => 'Gottesdienst',
            'offering_goal' => 'Eigene Gemeinde',
            'offering_amount' => '125,00',
        ]);
    }

    public function test_capture_reports_index(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('reports.list'),
                'berichte-uebersicht',
                800
            );
        });
    }

    public function test_capture_service_table_report_setup(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('reports.setup', 'ServiceTable'),
                'berichte-jahresplan-gottesdienste',
                800
            );
        });
    }

    public function test_capture_service_excel_table_report_setup(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('reports.setup', 'ServiceExcelTable'),
                'berichte-excel-tabelle-gottesdienste',
                800
            );
        });
    }

    public function test_capture_offering_certificate_report_setup(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('reports.setup', 'offeringCertificate'),
                'berichte-opferurkunde-auswahl',
                800
            );
        });
    }
}
