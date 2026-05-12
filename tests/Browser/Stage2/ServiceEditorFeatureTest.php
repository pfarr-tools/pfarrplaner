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
use App\Models\Sermon;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;
use Tests\Browser\Pages\ServiceEditorPage;

class ServiceEditorFeatureTest extends AbstractPageLoadTest
{
    protected Service $service;
    protected Sermon $sermon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = Service::factory()->create();
        $this->sermon  = Sermon::create(['title' => 'Testpredigt']);
        $this->service->update(['sermon_id' => $this->sermon->id]);
    }

    public function testServiceEditorRendersForm(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new ServiceEditorPage($this->service->slug))
                    ->assertPresent('#formSermon');
        });
    }

    public function testDescriptionFieldCanBeEdited(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new ServiceEditorPage($this->service->slug))
                    ->waitFor('[name="description"]', 10)
                    ->type('[name="description"]', 'Neue Testbeschreibung')
                    ->assertInputValue('[name="description"]', 'Neue Testbeschreibung');
        });
    }

    public function testSaveButtonIsPresent(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new ServiceEditorPage($this->service->slug))
                    ->assertPresent('button.btn-primary');
        });
    }

    public function testInternalRemarksCanBeEdited(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new ServiceEditorPage($this->service->slug))
                    ->waitFor('[name="internal_remarks"]', 10)
                    ->type('[name="internal_remarks"]', 'Interne Anmerkung')
                    ->assertInputValue('[name="internal_remarks"]', 'Interne Anmerkung');
        });
    }

    public function testSaveServiceNavigatesOrShowsNoError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new ServiceEditorPage($this->service->slug))
                    ->waitFor('button.btn-primary', 10)
                    ->click('button.btn-primary')
                    ->pause(1000)
                    ->assertDontSee('Whoops')
                    ->assertDontSee('500');
        });
    }
}
