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

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Provides access to the authenticated upload Inbox. */
class InboxController extends Controller
{
    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

    /** Return the list of allowed Inbox files. */
    public function index(): JsonResponse
    {
        $inboxPath = config('inbox.path');
        if (! is_string($inboxPath) || $inboxPath === '') {
            return response()->json(false);
        }

        $files = collect(glob(storage_path($inboxPath.'/*')) ?: [])->filter(function (string $item) {
            return is_file($item) && in_array(pathinfo($item, PATHINFO_EXTENSION), self::ALLOWED_EXTENSIONS);
        })->map(function ($item) {
            $extension = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            $size = filesize($item);
            $createdAt = filectime($item);
            if ($size === false || $createdAt === false) {
                return null;
            }

            return [
                'name' => basename($item),
                'extension' => $extension,
                'filesize' => $size,
                'icon' => $extension == 'pdf' ? 'mdi mdi-file-pdf-box' : 'fa fa-file-image',
                'date' => Carbon::createFromTimestamp($createdAt)->toIso8601String(),
            ];
        })->filter();

        return response()->json($files->values()->all());
    }

    /** Download an allowed Inbox file for local browser processing. */
    public function download(string $filename): BinaryFileResponse
    {
        $inboxPath = config('inbox.path');
        $directory = is_string($inboxPath) && $inboxPath !== '' ? realpath(storage_path($inboxPath)) : false;
        $basename = basename($filename);
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        $path = $directory ? realpath($directory.'/'.$basename) : false;

        abort_unless(
            $directory && $path && str_starts_with($path, $directory.DIRECTORY_SEPARATOR)
                && in_array($extension, self::ALLOWED_EXTENSIONS, true),
            404
        );

        return response()->download($path, $basename);
    }
}
