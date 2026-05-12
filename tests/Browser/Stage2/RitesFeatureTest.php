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

use App\Models\Places\City;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Service;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;
use Tests\Browser\Pages\FuneralEditorPage;

class RitesFeatureTest extends AbstractPageLoadTest
{
    protected Funeral $funeral;
    protected Service $service;
    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->city    = City::factory()->create();
        $this->service = Service::factory()->create(['city_id' => $this->city->id]);
        $this->funeral = Funeral::factory()->create();
    }

    public function testFuneralEditorRendersWithoutError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new FuneralEditorPage($this->funeral->id))
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testFuneralEditorHasBuriedNameField(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new FuneralEditorPage($this->funeral->id))
                    ->waitFor('[name="buried_name"]', 10)
                    ->assertPresent('[name="buried_name"]');
        });
    }

    public function testFuneralEditorBuriedNameCanBeEdited(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new FuneralEditorPage($this->funeral->id))
                    ->waitFor('[name="buried_name"]', 10)
                    ->clear('[name="buried_name"]')
                    ->type('[name="buried_name"]', 'Max Mustermann')
                    ->assertInputValue('[name="buried_name"]', 'Max Mustermann');
        });
    }

    public function testRitesIndexRendersWithoutError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('rites.index'))
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testFuneralWizardRendersWithoutError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('funerals.wizard'))
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }
}
