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

use App\Models\Sermon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\AbstractSimpleModelUnitTest;

class SermonUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Sermon::class;
    protected bool $hasPolicy = false;
    protected bool $hasFactory = false;

    public function testSermonHasServicesRelationship(): void
    {
        $sermon = Sermon::create(['title' => 'Test Predigt']);
        $this->assertInstanceOf(HasMany::class, $sermon->services());
    }

    public function testSermonCanBeCreatedDirectly(): void
    {
        $sermon = Sermon::create(['title' => 'Test Predigt', 'reference' => 'Johannes 3,16']);
        $this->assertCount(1, Sermon::all());
        $this->assertEquals('Test Predigt', Sermon::first()->title);
    }
}
