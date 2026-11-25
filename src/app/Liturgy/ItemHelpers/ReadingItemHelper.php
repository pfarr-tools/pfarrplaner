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
 *
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

class ReadingItemHelper extends AbstractItemHelper
{
    /**
     * Geparste Referenz der Schriftlesung mit Version und Copyright-Angabe.
     *
     * @var array{
     *     originalReference: string,
     *     correctedReference: string,
     *     version: string,
     *     versionCopyrights: string,
     *     parsed: array<int, array<string, mixed>>
     * }
     */
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
     * Liefert den Bibeltext der Schriftlesung als Abschnitte mit Versen.
     *
     * @return array<int, array<string, mixed>> Abschnitte mit je einem text-Eintrag.
     */
    public function getText(): array
    {
        // return (new BibleText('LUT17'))->get($this->reference);
        if ($this->reference['version'] == 'Eigener Text') {
            return [
                ['text' => [['text' => $this->item->data['customText']]]],
            ];
        }

        return (new BibleText($this->reference['version']))->get($this->reference);
    }

    /**
     * Liefert die Schriftlesung als HTML für die öffentliche Ansicht.
     *
     * @return string HTML der Lesung mit Versnummern oder der eigene Text.
     */
    public function getHtml(): string
    {
        if ($this->reference['version'] == 'Eigener Text') {
            return $this->item->data['customText'];
        }
        $bibleText = (new BibleText($this->reference['version']))->get($this->reference);
        $text = '';
        foreach ($bibleText as $section) {
            foreach ($section['text'] as $verse) {
                $text .= '<sup>'.$verse['verse'].'</sup> '.$verse['text'];
            }
        }

        return $text;
    }

    /**
     * Rendert die Schriftlesung in ein Word-Dokument.
     *
     * @param  DefaultWordDocument  $doc  Dokument, in das die Lesung gerendert wird.
     * @param  bool  $includeFullReadings  Ob der vollständige Bibeltext eingefügt wird.
     * @param  bool  $includeIntro  Ob die Einleitung der Lesung eingefügt wird.
     * @param  string  $includeTitle  Alternativer Titel; leer für die korrigierte Referenz.
     * @param  int  $depth  Überschriftenebene des Titels.
     */
    public function renderToWordDocument(DefaultWordDocument $doc, bool $includeFullReadings = true, bool $includeIntro = false, string $includeTitle = '', int $depth = 3): void
    {
        $doc->getSection()->addTitle($includeTitle ?: $this->reference['correctedReference'], $depth);
        if (! $includeFullReadings) {
            return;
        }
        if ($this->reference['versionCopyrights']) {
            $doc->renderNormalText($this->reference['versionCopyrights'], ['size' => 8], $doc->getConfig()['styles']['paragraphs']['default']['spaceAfter'] == 0);
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
                    $run[] = [$verse['verse'].' ', ['superScript' => true]];
                }
                $run[] = [$verse['text']."\n", []];
            }
        }

        $doc->renderParagraph($doc::NORMAL, $run);

    }

    /**
     * Liefert die geparste Referenz der Schriftlesung.
     *
     * @return array{
     *     originalReference: string,
     *     correctedReference: string,
     *     version: string,
     *     versionCopyrights: string,
     *     parsed: array<int, array<string, mixed>>
     * }
     */
    public function getReference(): array
    {
        return $this->reference;
    }

    /**
     * Setzt die geparste Referenz der Schriftlesung.
     *
     * @param  array{
     *     originalReference: string,
     *     correctedReference: string,
     *     version: string,
     *     versionCopyrights: string,
     *     parsed: array<int, array<string, mixed>>
     * }  $reference  Geparste Referenz inklusive Version und Copyright-Angabe.
     */
    public function setReference(array $reference): void
    {
        $this->reference = $reference;
    }
}
