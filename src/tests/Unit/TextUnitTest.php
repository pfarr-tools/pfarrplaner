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

use App\Models\Liturgy\Text;
use Tests\AbstractSimpleModelUnitTest;

class TextUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Text::class;
    protected bool $hasPolicy = true;
    protected bool $hasFactory = true;

    public function testTextCanBeCreatedViaFactory(): void
    {
        $text = Text::factory()->create();
        $this->assertCount(1, Text::all());
        $this->assertNotEmpty($text->title);
    }
}
