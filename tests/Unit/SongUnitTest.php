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

namespace Tests\Unit;

use App\Models\Liturgy\Song;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\AbstractSimpleModelUnitTest;

class SongUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Song::class;
    protected bool $hasPolicy = true;
    protected bool $hasFactory = true;

    public function testSongHasVersesRelationship(): void
    {
        $song = Song::factory()->create();
        $this->assertInstanceOf(HasMany::class, $song->verses());
    }

    public function testSongHasSongbooksRelationship(): void
    {
        $song = Song::factory()->create();
        $this->assertInstanceOf(BelongsToMany::class, $song->songbooks());
    }

    public function testSongCanBeCreatedViaFactory(): void
    {
        $song = Song::factory()->create();
        $this->assertCount(1, Song::all());
    }
}
