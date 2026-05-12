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

namespace Tests\Browser\Stage1;

use App\Models\Rites\Funeral;
use App\Models\Service;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class RitesPageLoadTest extends AbstractPageLoadTest
{
    protected Service $service;
    protected Funeral $funeral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = Service::factory()->create();
        $this->funeral = Funeral::factory()->create(['service_id' => $this->service->id]);
    }

    public function testRitesIndexLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('rites.index'));
        });
    }

    public function testFuneralEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('funerals.edit', $this->funeral->id));
        });
    }

    public function testFuneralWizardLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('funerals.wizard'));
        });
    }

    public function testBaptismAddLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('baptism.add', $this->service->id));
        });
    }

    public function testWeddingWizardLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('weddings.wizard'));
        });
    }

    public function testWeddingEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('wedding.add', $this->service->id));
        });
    }
}
