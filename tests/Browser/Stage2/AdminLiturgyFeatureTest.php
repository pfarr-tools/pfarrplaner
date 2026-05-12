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

use App\Models\Liturgy\Psalm;
use App\Models\Liturgy\Song;
use App\Models\Liturgy\Songbook;
use App\Models\Liturgy\Text;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class AdminLiturgyFeatureTest extends AbstractPageLoadTest
{
    protected Song $song;
    protected Psalm $psalm;
    protected Songbook $songbook;
    protected Text $text;

    protected function setUp(): void
    {
        parent::setUp();
        $this->song     = Song::factory()->create(['title' => 'Testlied']);
        $this->psalm    = Psalm::factory()->create(['title' => 'Testpsalm']);
        $this->songbook = Songbook::factory()->create(['name' => 'Testgesangbuch']);
        $this->text     = Text::factory()->create(['title' => 'Testtext', 'text' => 'Testinhalt']);
    }

    public function testSongsIndexListsSongs(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('songs.index'))
                    ->waitFor('#app', 10)
                    ->assertSee('Testlied');
        });
    }

    public function testSongEditorHasTitleField(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('song.edit', $this->song->id))
                    ->waitFor('[name="title"]', 10)
                    ->assertInputValue('[name="title"]', 'Testlied');
        });
    }

    public function testSongEditorTitleCanBeEdited(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('song.edit', $this->song->id))
                    ->waitFor('[name="title"]', 10)
                    ->clear('[name="title"]')
                    ->type('[name="title"]', 'Geändertes Lied')
                    ->assertInputValue('[name="title"]', 'Geändertes Lied');
        });
    }

    public function testPsalmEditorHasTitleField(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('psalm.edit', $this->psalm->id))
                    ->waitFor('[name="title"]', 10)
                    ->assertInputValue('[name="title"]', 'Testpsalm');
        });
    }

    public function testSongbookEditorHasNameField(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('songbook.edit', $this->songbook->id))
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testLiturgicalTextEditorHasTitleField(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('admin.text.edit', $this->text->id))
                    ->waitFor('[name="title"]', 10)
                    ->assertInputValue('[name="title"]', 'Testtext');
        });
    }
}
