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

use App\Models\Liturgy\Psalm;
use Tests\AbstractSimpleModelUnitTest;

class PsalmUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Psalm::class;
    protected bool $hasPolicy = true;
    protected bool $hasFactory = true;

    public function testPsalmCanBeCreatedViaFactory(): void
    {
        $psalm = Psalm::factory()->create();
        $this->assertCount(1, Psalm::all());
        $this->assertNotEmpty($psalm->title);
    }

    public function testPsalmTitleIsRequired(): void
    {
        $psalm = Psalm::factory()->create(['title' => 'Test Psalm']);
        $this->assertEquals('Test Psalm', $psalm->fresh()->title);
    }
}
