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

namespace App\Reports;

use Illuminate\Support\Facades\Response;

abstract class AbstractCSVReport extends AbstractReport
{

    /**
     * @var string
     */
    public $group = 'CSV-Datenausgabe';
    /**
     * @var string
     */
    public $icon = 'fa fa-file-lines';

    protected function csv($fileName, $data, $options)
    {
        $records = [];
        foreach ($data as $key => $item) {
            $record = [];
            foreach ($options as $recordKey => $option) {
                if (is_numeric($recordKey)) $recordKey = $option;
                $record[$recordKey] = $option instanceof \Closure ? $option($item, $key) : $this->getPropertyByKey($option, $item);
            }
            $records[] = $record;
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $handle = fopen(storage_path($fileName), 'w');
        if (isset($records[0])) {
            fputcsv($handle, array_keys($records[0]));
        }

        foreach ($records as $record) fputcsv($handle, array_values($record));
        fclose ($handle);

        return Response::download(storage_path($fileName), $fileName, $headers)->deleteFileAfterSend(true);

    }

    protected function getPropertyByKey($recordKey, $item)
    {
        if (is_object($item)) return $item->{$recordKey};
        return $item[$recordKey] ?? '';
    }


}
