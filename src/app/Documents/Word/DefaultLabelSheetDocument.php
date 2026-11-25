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

    /**
     * Render label records into a grid of table cells.
     *
     * @param  iterable<mixed>  $data  Label records to render.
     * @param  int  $columns  Number of label columns per row.
     * @param  int|float  $width  Cell width in twips.
     * @param  int|float  $height  Row height in twips.
     * @param  array<string, mixed>  $cellStyle  Style applied to each cell.
     * @param  callable  $renderFunction  Callback rendering one cell.
     * @param  int  $skipLabels  Number of leading labels to leave blank.
     * @return void
     */
    public function renderLabels($data, $columns, $width, $height, $cellStyle, $renderFunction, $skipLabels = 0)
    {
        $table = $this->section->addTable();
        $x = 0;
        $index = 0;
        $row = null;

        for ($i = 0; $i < $skipLabels; $i++) {
            if (($x == 0) || ($row === null)) {
                $row = $table->addRow((int) $height);
            }
            $row->addCell((int) $width, $cellStyle)->addText('  ');
            $x = ($x + 1) % $columns;
        }

        foreach ($data as $record) {
            if (($x == 0) || ($row === null)) {
                $row = $table->addRow((int) $height);
            }
            $cell = $row->addCell((int) $width, $cellStyle);
            $renderFunction($cell, $record, $index);
            $index++;
            $x = ($x + 1) % $columns;
        }
    }
}
