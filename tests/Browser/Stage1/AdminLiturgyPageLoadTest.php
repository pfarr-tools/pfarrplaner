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

use App\Models\Liturgy\Psalm;
use App\Models\Liturgy\Song;
use App\Models\Liturgy\Songbook;
use App\Models\Liturgy\Text;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class AdminLiturgyPageLoadTest extends AbstractPageLoadTest
{
    protected Song $song;
    protected Psalm $psalm;
    protected Songbook $songbook;
    protected Text $text;

    protected function setUp(): void
    {
        parent::setUp();
        $this->song = Song::create(['title' => 'Testlied']);
        $this->psalm = Psalm::create(['title' => 'Testpsalm']);
        $this->songbook = Songbook::create(['name' => 'Testgesangbuch']);
        $this->text = Text::create(['title' => 'Testtext', 'text' => 'Testinhalt']);
    }

    public function testSongsIndexLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('songs.index'));
        });
    }

    public function testSongEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('song.edit', $this->song->id));
        });
    }

    public function testPsalmsIndexLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('psalms.index'));
        });
    }

    public function testPsalmEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('psalm.edit', $this->psalm->id));
        });
    }

    public function testSongbooksIndexLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('songbooks.index'));
        });
    }

    public function testSongbookEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('songbook.edit', $this->songbook->id));
        });
    }

    public function testLiturgicalTextsIndexLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('admin.text.index'));
        });
    }

    public function testLiturgicalTextEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('admin.text.edit', $this->text->id));
        });
    }

    public function testMusicEditorLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('liturgy.song.musiceditor', $this->song->id));
        });
    }
}
