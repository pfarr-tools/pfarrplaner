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

use App\Models\Sermon;
use App\Models\Service;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class ServicesPageLoadTest extends AbstractPageLoadTest
{
    protected Service $service;
    protected Sermon $sermon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = Service::factory()->create();
        $this->sermon = Sermon::create(['title' => 'Testpredigt']);
        $this->service->update(['sermon_id' => $this->sermon->id]);
    }

    public function testServiceEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('service.edit', $this->service->slug));
        });
    }

    public function testLiturgyEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('liturgy.editor', $this->service->slug));
        });
    }

    public function testSermonEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('sermon.editor', $this->sermon->id));
        });
    }

    public function testSermonReaderLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('sermon.reader', $this->sermon->id));
        });
    }
}
