<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 */

namespace Tests\Unit\Liturgy;

use App\Liturgy\LiturgySheets\OrganistLiturgySheet;
use Tests\TestCase;

/** Covers the title of the organist-oriented liturgy sheet. */
class OrganistLiturgySheetUnitTest extends TestCase
{
    /** The download label uses an inclusive musician title. */
    public function test_title_uses_the_inclusive_musician_label(): void
    {
        $sheet = new OrganistLiturgySheet;

        $this->assertSame('Ablaufplan für Musiker:innen (DIN A4)', $sheet->getTitle());
    }
}
