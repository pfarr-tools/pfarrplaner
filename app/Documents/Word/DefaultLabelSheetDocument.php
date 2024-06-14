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

namespace App\Documents\Word;

use PhpOffice\PhpWord\Shared\Converter;

class DefaultLabelSheetDocument extends DefaultWordDocument
{

    protected function configureLayout($config)
    {
        $this->section = $this->phpWord->addSection(
            array_merge(
                [
                    'orientation' => 'portrait',
                    'pageSizeH' => Converter::cmToTwip(29.7),
                    'pageSizeW' => Converter::cmToTwip(21),
                    'marginTop' => Converter::cmToTwip(0.45),
                    'marginBottom' => Converter::cmToTwip(0),
                    'marginLeft' => Converter::cmToTwip(0),
                    'marginRight' => Converter::cmToTwip(0),
                ],
                $config
            )
        );
    }

    public function renderLabels($data, $columns, $width, $height, $cellStyle, $renderFunction)
    {
        $table = $this->section->addTable();
        $x = 0;
        $index = 0;
        foreach ($data as $record) {
            if ($x == 0) $row = $table->addRow($height);
            $cell = $row->addCell($width, $cellStyle);
            $renderFunction($cell, $record, $index);
            $index++;
            $x = ($x+1) % $columns;
        }
    }


}
