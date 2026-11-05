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

namespace Tests\Browser\Stage2;

use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class ReportsFeatureTest extends AbstractPageLoadTest
{
    public function test_reports_index_lists_reports(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('reports.list'))
                ->waitFor('#app', 10)
                ->assertDontSee('500')
                ->assertDontSee('Whoops');
        });
    }

    public function test_reports_index_renders_at_least_one_report(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('reports.list'))
                ->waitFor('#app', 10)
                ->assertPresent('a, .card, .list-group-item');
        });
    }

    public function test_reports_index_lists_both_service_table_reports(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('reports.list'))
                ->waitFor('#app', 10)
                ->assertSee('Jahresplan der Gottesdienste')
                ->assertSee('Excel-Tabelle der Gottesdienste');
        });
    }

    public function test_service_table_report_setup_renders_without_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('reports.setup', 'ServiceTable'))
                ->waitFor('#app', 10)
                ->assertDontSee('500')
                ->assertDontSee('Whoops');
        });
    }

    public function test_service_table_report_has_render_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('reports.setup', 'ServiceTable'))
                ->waitFor('#app', 10)
                ->assertPresent('button[type="submit"], button.btn-primary, .btn-primary');
        });
    }

    public function test_service_excel_table_report_setup_renders_without_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('reports.setup', 'ServiceExcelTable'))
                ->waitFor('#app', 10)
                ->assertDontSee('500')
                ->assertDontSee('Whoops');
        });
    }

    public function test_service_excel_table_report_has_render_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('reports.setup', 'ServiceExcelTable'))
                ->waitFor('#app', 10)
                ->assertPresent('button[type="submit"], button.btn-primary, .btn-primary');
        });
    }

    public function test_service_excel_table_report_shows_new_configuration_fields(): void
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
