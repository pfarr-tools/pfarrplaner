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

namespace App\Attachments\Baptism;

use App\Documents\Word\DefaultAddressLabelSheetDocument;
use App\Models\Rites\Baptism;
use App\Services\NameService;
use PhpOffice\PhpWord\Element\Cell;

class BaptismLettersAddressLabelsAttachment extends AbstractBaptismAttachment
{

    protected static $title = 'Adressetiketten für Taufbriefe';
    protected static $description = 'Für die Taufjubiläen im 1.-9. Jahr nach der Taufe';
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
        'spaceBefore' => 120
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

    public function __construct(Baptism $baptism)
    {
        parent::__construct($baptism);
        static::$fileTitle = $baptism->service->date->format(
                'Ymd'
            ) . ' ' . $baptism->candidate_name . ' ' . static::$title;
    }

    public function download()
    {
        $doc = new DefaultAddressLabelSheetDocument();

        $data = [];
        for ($i=1; $i<=9; $i++) {
            $data[] = [
                'name' => NameService::fromName($this->baptism->candidate_name)->format(NameService::FIRST_LAST),
                'address' => $this->baptism->candidate_address,
                'zip' => $this->baptism->candidate_zip,
                'city' => $this->baptism->candidate_city,
                'info' => $this->baptism->service->date->copy()->addYears($i)->format('d.m.Y').' / #'.$i,
            ];
        }

        $doc->renderAddresses($data, '');

        return $doc->sendToBrowser(static::getFileName());
    }

    public function renderCell (Cell $cell, $record, $index) {
        $cell->addText($record.' | '.$index);
    }

}
