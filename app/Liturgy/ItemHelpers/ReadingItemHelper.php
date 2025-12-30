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
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Liturgy\ItemHelpers;

use App\Documents\Word\DefaultWordDocument;
use App\Liturgy\Bible\BibleText;
use App\Liturgy\Bible\ReferenceParser;
use App\Models\Liturgy\Item;
use Illuminate\Support\Str;

class ReadingItemHelper extends AbstractItemHelper
{
    /** @var array  */
    protected $reference;

    /**
     * Create a new ReadingItemHelper instance.
     */
    public function __construct(Item $item)
    {
        parent::__construct($item);
        $this->reference = ReferenceParser::getInstance()->parse($item->data['reference']);

        if ($this->reference['version'] == 'Eigener Text') {
            $this->reference['versionCopyrights'] = $this->item->data['customSource'] ?? '';
        }
    }

    /**
     * Get the bible text for this reading item.
     */
    public function getText()
    {
        //return (new BibleText('LUT17'))->get($this->reference);
        if ($this->reference['version'] == 'Eigener Text') {
            return [
                ['text' => [['text' => $this->item->data['customText']]]]
            ];
        }
        return (new BibleText($this->reference['version']))->get($this->reference);
    }

    public function renderToWordDocument(DefaultWordDocument $doc, $includeFullReadings = true, $includeIntro = false)
    {
        $doc->getSection()->addTitle($this->reference['correctedReference'], 3);
        if (!$includeFullReadings) {
            return;
        }
        if ($this->reference['versionCopyrights']) {
            $doc->renderNormalText($this->reference['versionCopyrights'], ['size' => 8]);
        }

        // Include intro to reading?
        if ($includeIntro && ($intro = $this->item->data['intro'] ?? false)) {
            foreach (explode("\n", $intro) as $line) {
                $doc->renderParagraph($doc::NORMAL, [[$line, ['italic' => true]]]);
            }
        }

        $bibleText = $this->getText();

        $run = [];
        foreach ($bibleText as $range) {
            foreach ($range['text'] as $verse) {
                if ($range['verse'] ?? false) {
                    $run[] = [$verse['verse'] . ' ', ['superScript' => true]];
                }
                $run[] = [$verse['text'] . "\n", []];
            }
        }

        $doc->renderParagraph($doc::NORMAL, $run);

    }

    public function getReference(): array
    {
        return $this->reference;
    }

    public function setReference(array $reference): void
    {
        $this->reference = $reference;
    }



}
