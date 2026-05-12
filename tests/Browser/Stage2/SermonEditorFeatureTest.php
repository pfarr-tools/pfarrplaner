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

use App\Models\Sermon;
use App\Models\Service;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class SermonEditorFeatureTest extends AbstractPageLoadTest
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

    public function testSermonEditorRendersForm(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('sermon.editor', $this->sermon->id))
                    ->waitFor('#formSermon', 10)
                    ->assertDontSee('500');
        });
    }

    public function testSermonEditorHasSaveButton(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('sermon.editor', $this->sermon->id))
                    ->waitFor('#app', 10)
                    ->assertSee('Speichern');
        });
    }

    public function testSermonReaderShowsTitle(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('sermon.reader', $this->sermon->id))
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertSee('Testpredigt');
        });
    }

    public function testSermonSaveNavigatesOrShowsNoError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('sermon.editor', $this->sermon->id))
                    ->waitFor('button.btn-primary', 10)
                    ->click('button.btn-primary')
                    ->pause(1000)
                    ->assertDontSee('Whoops')
                    ->assertDontSee('500');
        });
    }
}
