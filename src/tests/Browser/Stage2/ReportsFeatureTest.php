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

namespace Tests\Browser\Stage2;

use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class ReportsFeatureTest extends AbstractPageLoadTest
{
    public function testReportsIndexListsReports(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('reports.list'))
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testReportsIndexRendersAtLeastOneReport(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('reports.list'))
                    ->waitFor('#app', 10)
                    ->assertPresent('a, .card, .list-group-item');
        });
    }

    public function testReportsIndexListsBothServiceTableReports(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('reports.list'))
                    ->waitFor('#app', 10)
                    ->assertSee('Jahresplan der Gottesdienste')
                    ->assertSee('Excel-Tabelle der Gottesdienste');
        });
    }

    public function testServiceTableReportSetupRendersWithoutError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('reports.setup', 'ServiceTable'))
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testServiceTableReportHasRenderButton(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('reports.setup', 'ServiceTable'))
                    ->waitFor('#app', 10)
                    ->assertPresent('button[type="submit"], button.btn-primary, .btn-primary');
        });
    }

    public function testServiceExcelTableReportSetupRendersWithoutError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('reports.setup', 'ServiceExcelTable'))
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testServiceExcelTableReportHasRenderButton(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('reports.setup', 'ServiceExcelTable'))
                    ->waitFor('#app', 10)
                    ->assertPresent('button[type="submit"], button.btn-primary, .btn-primary');
        });
    }

    public function testServiceExcelTableReportShowsNewConfigurationFields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('reports.setup', 'ServiceExcelTable'))
                    ->waitFor('#app', 10)
                    ->assertSee('Zeitraum')
                    ->assertSee('Folgende weiteren Dienste mit einschließen')
                    ->assertSee('Lesbare Überschriften')
                    ->assertSee('Nur Gottesdienste');
        });
    }
}
