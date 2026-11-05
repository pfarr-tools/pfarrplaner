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

use Laravel\Dusk\Browser;

class ReportsScreenshotTest extends ManualScreenshotTestCase
{
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
}
