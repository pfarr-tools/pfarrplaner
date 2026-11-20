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

use App\Documents\Word\DefaultWordDocument;
use App\Liturgy\LiturgySheets\PsalmLiturgySheet;
use App\Models\Liturgy\Item;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Covers Psalm sheet rendering when liturgy item data is incomplete.
 */
class PsalmLiturgySheetUnitTest extends TestCase
{
    /**
     * Rendering skips a liturgy item that does not contain Psalm data.
     */
    public function test_missing_psalm_data_is_skipped(): void
    {
        $item = new Item;
        $item->data = ['other' => ['text' => 'No Psalm']];
        $document = (new ReflectionClass(DefaultWordDocument::class))->newInstanceWithoutConstructor();
        $sheet = new PsalmLiturgySheet;
        $method = new ReflectionMethod($sheet, 'renderPsalmItem');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($sheet, $document, $item));
    }
}
