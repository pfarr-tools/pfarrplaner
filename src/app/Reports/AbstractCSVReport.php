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

namespace App\Reports;

use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Base class for CSV exports. */
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

    /**
     * Create a CSV download from the given records and column definitions.
     *
     * @param  iterable<object|array<array-key, mixed>>  $data
     * @param  array<array-key, string|\Closure>  $options
     */
    protected function csv(string $fileName, iterable $data, array $options): BinaryFileResponse
    {
        $records = [];
        foreach ($data as $key => $item) {
            $record = [];
            foreach ($options as $recordKey => $option) {
                if (is_numeric($recordKey) && is_string($option)) {
                    $recordKey = $option;
                }
                $record[$recordKey] = $option instanceof \Closure ? $option($item, $key) : $this->getPropertyByKey($option, $item);
            }
            $records[] = $record;
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ];

        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        if ($tempFile === false) {
            throw new \RuntimeException('Temporäre CSV-Datei konnte nicht erstellt werden.');
        }

        $handle = fopen($tempFile, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Temporäre CSV-Datei konnte nicht geöffnet werden.');
        }
        if (isset($records[0])) {
            fputcsv($handle, array_keys($records[0]));
        }

        foreach ($records as $record) {
            fputcsv($handle, array_values($record));
        }
        fclose($handle);

        return Response::download($tempFile, $fileName, $headers)->deleteFileAfterSend(true);

    }

    /**
     * Read a column value from an object property or an array key.
     *
     * @param  object|array<array-key, mixed>  $item
     */
    protected function getPropertyByKey(string $recordKey, object|array $item): mixed
    {
        if (is_object($item)) {
            return $item->{$recordKey};
        }

        return $item[$recordKey] ?? '';
    }
}
