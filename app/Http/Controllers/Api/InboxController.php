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

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;

class InboxController extends Controller
{

    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

    public function index()
    {
        $inboxPath = config('inbox.path');
        if (!$inboxPath) return response()->json(false);

        $files = collect(glob(storage_path($inboxPath.'/*')))->filter(function ($item) {
            return in_array(pathinfo($item, PATHINFO_EXTENSION), static::ALLOWED_EXTENSIONS);
        })->map(function ($item) {
            $extension = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            return [
                'name' => basename($item),
                'extension' => $extension,
                'filesize' => filesize($item),
                'icon' => $extension == 'pdf' ? 'mdi mdi-file-pdf-box' : 'fa fa-file-image',
                'date' => Carbon::createFromTimestamp(filectime($item))->toIso8601String(),
            ];
        });
        return response()->json($files->values()->all());
    }

}
