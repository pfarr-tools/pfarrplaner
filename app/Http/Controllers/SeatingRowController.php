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

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Seating\SeatingRow;
use Illuminate\Http\Request;

class SeatingRowController extends AbstractCRUDController
{
    protected string $modelClass = SeatingRow::class;

    protected function preFillNewModel(Request $request): array
    {
        /** @var Location $location */
        $location = $request->route('location');

        return [
            'title' => '',
            'seats' => 1,
            'split' => '',
            'divides_into' => 1,
            'spacing' => 0,
            'color' => '',
            'seating_section_id' => $location->seatingSections->first()->id,
        ];
    }

    protected function getResourcesForEditor(Request $request, $model = null): array
    {
        $data = parent::getResourcesForEditor($request, $model);
        $data['location'] = $model ? $model->seatingSection->location : $request->route('location');
        return $data;
    }
}
