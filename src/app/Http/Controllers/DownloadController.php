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

use App\Attachments\AbstractAttachment;
use App\Attachments\AttachmentFactory;
use App\Helpers\FileHelper;
use App\Models\Attachment;
use App\Models\Service;
use App\Services\QRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class DownloadController
 * @package App\Http\Controllers
 */
class DownloadController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth')->except(['storage', 'file', 'image', 'qr']);
    }

    /**
     * @param Request $request
     * @param $storage
     * @param $code
     * @param string $prettyName
     * @return StreamedResponse
     */
    public function download(Request $request, $storage, $code, $prettyName = '')
    {
        $prettyName = $prettyName ?: '';
        return Storage::download(
            'public/' . $storage . '/' . $code . '.' . pathinfo($prettyName, PATHINFO_EXTENSION),
            FileHelper::normalizeFilename($prettyName)
        );
    }

    /**
     * @param Request $request
     * @param $attachment
     * @param string $prettyName
     * @return StreamedResponse
     */
    public function attachment(Request $request, Attachment $attachment, $prettyName = '')
    {
        $this->authorize('update', $attachment);

        $prettyName = $prettyName ? FileHelper::normalizeFilename($prettyName) :
            (FileHelper::normalizeFilename($attachment->title) ?? $attachment->id) . '.' . pathinfo(
                $attachment->file,
                PATHINFO_EXTENSION
            );
        return Storage::download($attachment->file, $prettyName);
    }

    /**
     * @param $path
     * @param string $prettyName
     * @return StreamedResponse
     */
    public function storage($path, $prettyName = '')
    {
        if (!Auth::check() && !request()->hasValidSignature()) {
            abort(401);
        }
        if ((pathinfo($path, PATHINFO_EXTENSION) == '') && (pathinfo($prettyName, PATHINFO_EXTENSION) != '')) {
            $path .= '.' . pathinfo($prettyName, PATHINFO_EXTENSION);
        }
        $prettyName = $prettyName ? FileHelper::normalizeFilename($prettyName) : basename($path);
        if (Storage::exists('attachments/' . $path)) {
            return Storage::download('attachments/' . $path, $prettyName);
        }
        abort(404);
    }


    /**
     * @param Request $request
     * @param $path
     * @param string $prettyName
     * @return StreamedResponse
     */
    public function image(Request $request, $path, $prettyName = '')
    {
        if (substr($path, 0, 12) == 'attachments/') $path = substr($path, 12);
        if ((pathinfo($path, PATHINFO_EXTENSION) == '') && (pathinfo($prettyName, PATHINFO_EXTENSION) != '')) {
            $path .= '.' . pathinfo($prettyName, PATHINFO_EXTENSION);
        }
        $prettyName = $prettyName ? FileHelper::normalizeFilename($prettyName) : basename($path);
        if (Storage::exists('attachments/' . $path)) {
            return $request->get('download', false ) ? Storage::download('attachments/'.$path) : Storage::response('attachments/' . $path);
        }
        abort(404);
    }


    /**
     * @param $value
     * @param string $prettyName
     */
    public function qr($value, $prettyName = '') {
        $file = QRService::generate($value);
        $png = file_get_contents($file);
        unlink($file);
        return response($png)->header('Content-Type', 'image/png');
    }


    public function autoAttachment($type, $attachable, $attachment)
    {
        return AttachmentFactory::get($type, $attachment, $attachable)->download();
    }

    public function autoAttachmentSetup($type, $attachable, $attachment)
    {
        return AttachmentFactory::get($type, $attachment, $attachable)->setup();
    }
}
