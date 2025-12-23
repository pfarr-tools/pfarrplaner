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

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Class AttachmentController
 * @package App\Http\Controllers
 */
class AttachmentController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * @param Request $request
     * @param Attachment $attachment
     */
    public function update(Request $request, Attachment $attachment)
    {
        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            $cut = $request->get('cut', null);
            foreach ($files as $key => $file) {
                if ($cut) {
                    //$extension = $file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                    $path = $file->storeAs('attachments', 'zuschnitt-'.$attachment->attachable_id.'-'.$cut.'.'.$file->getClientOriginalExtension());
                } else {
                    $path = $file->storeAs('attachments', 'attachments', Str::random(32).'.'.$file->getClientOriginalExtension());
                }
                $attachment->update(['file' => $path, 'cut' => $cut]);
            }
        }
        $attachment->refresh();
        return response()->json($attachment->attachable->attachments);
    }

}
