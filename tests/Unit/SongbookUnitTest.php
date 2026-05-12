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

use App\Models\Liturgy\Songbook;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\AbstractSimpleModelUnitTest;

class SongbookUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Songbook::class;
    protected bool $hasPolicy = true;
    protected bool $hasFactory = true;

    public function testSongbookHasSongsRelationship(): void
    {
        $songbook = Songbook::factory()->create();
        $this->assertInstanceOf(BelongsToMany::class, $songbook->songs());
    }

    public function testSongbookCanBeCreatedViaFactory(): void
    {
        $songbook = Songbook::factory()->create();
        $this->assertCount(1, Songbook::all());
        $this->assertNotEmpty($songbook->name);
    }
}
