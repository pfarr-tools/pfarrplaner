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

namespace App\Attachments\Baptism;

use App\Documents\Word\DefaultWordDocument;
use App\Services\ColorService;
use App\Services\NameService;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Shared\Converter;

class PfefferBibleInlayAttachment extends AbstractBaptismAttachment
{
    protected static $title = 'Bibeleinleger';

    protected static $description = 'Einlegeblatt für "Die bunte Kinderbibel"';

    protected static $extension = 'docx';

    protected static $icon = 'mdi mdi-file-word';

    protected static $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    protected const FONT18 = ['name' => 'Lucida Handwriting', 'size' => 18, 'bold' => true];

    protected const FONT20 = ['name' => 'Lucida Handwriting', 'size' => 20, 'bold' => true];

    protected const FONT34 = ['name' => 'Lucida Handwriting', 'size' => 34, 'bold' => true];

    protected const PARAGRAPH = [
        'align' => 'center',
        'spaceAfter' => 0,
    ];

    protected const PARAGRAPH_SPC_TOP = [
        'align' => 'center',
        'spaceAfter' => 0,
        'spaceBefore' => 120,
    ];

    protected const PARAGRAPH_SPC_BOTTOM = [
        'align' => 'center',
        'spaceAfter' => 120,
        'spaceBefore' => 0,
    ];

    protected const RAINBOW_COLORS = [
        '#ff002a', '#fd7400', '#fee002', '#01ac42', '#005efc', '#870dad', '#82491e', '#68d4f8', '#ff92bb',
    ];

    public function download()
    {
        $doc = new DefaultWordDocument([
            'layout' => [
                'orientation' => 'portrait',
                'pageSizeH' => Converter::cmToTwip(21),
                'pageSizeW' => Converter::cmToTwip(19.7),
                'marginTop' => Converter::cmToTwip(1.5),
                'marginBottom' => Converter::cmToTwip(2),
                'marginLeft' => Converter::cmToTwip(1),
                'marginRight' => Converter::cmToTwip(2.95),
            ],
            'defaultFontSize' => 20,
        ]);
        $section = $doc->getSection();
        $service = $this->baptism->service;
        /** @var string $candidateName */
        $candidateName = $this->baptism->candidate_name;
        $section->addText('Dieses Buch gehört', static::FONT20, static::PARAGRAPH_SPC_BOTTOM);
        $this->coloredText(
            $doc->getSection(),
            "Familie\n".NameService::fromName($candidateName)->getLastName(),
            self::FONT34,
            self::PARAGRAPH
        );
        $section->addText('Es ist ein Geschenk', static::FONT20, static::PARAGRAPH_SPC_TOP);
        $section->addText('zur Tauferinnerung', static::FONT20, static::PARAGRAPH);
        $section->addText('an die Taufe von', static::FONT20, static::PARAGRAPH);
        $section->addTextBreak(1);
        $this->coloredText(
            $doc->getSection(),
            NameService::fromName($candidateName)->getFirstName(),
            self::FONT34,
            self::PARAGRAPH
        );
        $section->addTextBreak(1);
        $section->addText(
            'am '.($service?->trueDate()->isoFormat('DD. MMMM YYYY') ?? ''),
            static::FONT18,
            static::PARAGRAPH
        );
        $section->addTextBreak(1);
        $section->addText('von der', static::FONT18, static::PARAGRAPH);
        $section->addText('Evang. Kirchengemeinde', static::FONT18, static::PARAGRAPH);
        $section->addText($service?->city->name ?? '', static::FONT18, static::PARAGRAPH);

        return $doc->sendToBrowser(static::getFileName());
    }

    /**
     * Render text character by character with alternating rainbow colors.
     *
     * @param  Section  $section  Section receiving the colored text.
     * @param  string  $text  Text to render in rainbow colors.
     * @param  array<string, mixed>  $fontStyle  Font style settings applied to each character.
     * @param  array<string, mixed>|string  $paragraphStyle  Paragraph style settings or style name.
     */
    protected function coloredText(
        Section $section,
        string $text,
        array $fontStyle,
        array|string $paragraphStyle
    ): void {
        $run = $section->addTextRun($paragraphStyle);

        $colors = ColorService::rainbowTextArray($text);
        $i = 0;
        foreach (mb_str_split($text) as $character) {
            if ($character == "\n") {
                $run->addTextBreak();
            } else {
                $run->addText($character, array_merge($fontStyle, [
                    'color' => $colors[$i],
                ]));
            }
            $i++;
        }
    }
}
