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

use App\Documents\Word\DefaultAddressLabelSheetDocument;
use App\Services\NameService;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpWord\Element\Cell;

/** Address labels attachment for baptism letters. */
class BaptismLettersAddressLabelsAttachment extends AbstractBaptismAttachment
{
    protected static $title = 'Adressetiketten für Taufbriefe';

    protected static $description = 'Für die Taufjubiläen im 1.-9. Jahr nach der Taufe';

    protected static $fileTitle = 'Adressetiketten für Taufbriefe';

    protected static $extension = 'docx';

    protected static $icon = 'mdi mdi-file-word';

    protected static $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    protected static $hasSetup = true;

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
        '#ff002a',
        '#fd7400',
        '#fee002',
        '#01ac42',
        '#005efc',
        '#870dad',
        '#82491e',
        '#68d4f8',
        '#ff92bb',
    ];

    /**
     * @return mixed
     */
    public function download()
    {
        $validated = request()->validate([
            'size' => 'nullable|numeric',
            'years' => 'nullable|int|min:1',
            'skipLabels' => 'nullable|int',
        ]);
        $config = array_merge([
            'size' => 3.7,
            'years' => 9,
            'skipLabels' => 0,
        ], is_array($validated) ? $validated : []);

        $doc = new DefaultAddressLabelSheetDocument([
            'label' => [
                'height' => $config['size'],
            ],
            'skipLabels' => $config['skipLabels'],
        ]);

        $data = [];
        for ($i = 1; $i <= $config['years']; $i++) {
            $data[] = [
                'name' => NameService::fromName(is_string($this->baptism->candidate_name) ? $this->baptism->candidate_name : '')->format(NameService::FIRST_LAST),
                'address' => $this->baptism->candidate_address,
                'zip' => $this->baptism->candidate_zip,
                'city' => $this->baptism->candidate_city,
                'info' => ($this->baptism->service->date ?? now())->copy()->addYears($i)->format('d.m.Y').' / #'.$i,
            ];
        }

        $doc->renderAddresses($data, '');

        return $doc->sendToBrowser(static::getFileName());
    }

    /**
     * Render a single record into one label cell.
     *
     * @param  Cell  $cell  Table cell receiving the text.
     * @param  string  $record  Record text to render.
     * @param  int  $index  Zero-based index of the label.
     */
    public function renderCell(Cell $cell, string $record, int $index): void
    {
        $cell->addText($record.' | '.$index);
    }

    /**
     * Render the setup page for the address labels.
     *
     * @return Response Inertia response with the setup page.
     */
    public function setup(): Response
    {
        return Inertia::render('Attachments/Baptism/BaptismLettersAddressLabels/Setup', ['baptism' => $this->baptism]);
    }
}
