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

use App\Models\Service;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;
use Tests\Browser\Pages\LiturgyEditorPage;

class LiturgyEditorFeatureTest extends AbstractPageLoadTest
{
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = Service::factory()->create();
    }

    public function testLiturgyEditorRendersWithoutError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new LiturgyEditorPage($this->service->slug))
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testLiturgyEditorLinksBackToServiceEditor(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new LiturgyEditorPage($this->service->slug))
                    ->waitFor('#app', 10)
                    ->assertPresent('a.btn-light');
        });
    }

    public function testPdfLinkIsRendered(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new LiturgyEditorPage($this->service->slug))
                    ->waitFor('#app', 10)
                    ->assertPresent('a[href*="liedblatt"], a[href*="pdf"], a.btn');
        });
    }

    public function testSettingsRoundedTimesTogglePresent(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new LiturgyEditorPage($this->service->slug))
                    ->waitFor('#app', 10)
                    ->assertSee('Zeitangaben runden');
        });
    }
}
