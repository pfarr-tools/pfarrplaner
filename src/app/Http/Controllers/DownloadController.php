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

namespace App\Http\Controllers;

use App\Attachments\AbstractAttachment;
use App\Attachments\AttachmentFactory;
use App\Attachments\Baptism\BaptismLettersAddressLabelsAttachment;
use App\Helpers\FileHelper;
use App\Models\Attachment;
use App\Services\QRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Stellt Dateien aus dem Objektspeicher sowie automatisch erzeugte Attachments
 * zum Herunterladen bereit.
 */
class DownloadController extends Controller
{
    /**
     * Beschränkt alle Aktionen außer den öffentlich erreichbaren Downloads auf
     * angemeldete Benutzer.
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['storage', 'file', 'image', 'qr']);
    }

    /**
     * Lädt eine Datei aus einem öffentlichen Speicherordner herunter.
     *
     * @param  Request  $request  Die aktuelle HTTP-Anfrage.
     * @param  string  $storage  Name des Speicherordners.
     * @param  string  $code  Dateicode innerhalb des Speicherordners.
     * @param  string  $prettyName  Dateiname für den Download.
     * @return StreamedResponse Gestreamte Dateiantwort.
     */
    public function download(Request $request, $storage, $code, $prettyName = '')
    {
        $prettyName = is_string($prettyName) ? $prettyName : '';

        $downloadName = $this->normalizeDownloadFilename($prettyName);

        return Storage::download(
            'public/'.$storage.'/'.$code.'.'.pathinfo($prettyName, PATHINFO_EXTENSION),
            $downloadName
        );
    }

    /**
     * Lädt ein Attachment herunter, sofern der Benutzer es aktualisieren darf.
     *
     * @param  Request  $request  Die aktuelle HTTP-Anfrage.
     * @param  Attachment  $attachment  Das herunterzuladende Attachment.
     * @param  string  $prettyName  Dateiname für den Download.
     * @return StreamedResponse Gestreamte Dateiantwort.
     */
    public function attachment(Request $request, Attachment $attachment, $prettyName = '')
    {
        $this->authorize('update', $attachment);
        $prettyName = is_string($prettyName) ? $prettyName : '';

        $downloadName = $prettyName ? $this->normalizeDownloadFilename($prettyName) :
            $this->normalizeDownloadFilename($attachment->title).'.'.pathinfo(
                $attachment->file,
                PATHINFO_EXTENSION
            );

        return Storage::download($attachment->file, $downloadName);
    }

    /**
     * Lädt eine Datei aus dem Attachments-Speicher herunter.
     *
     * Der Download ist auch über eine signierte URL ohne Anmeldung möglich.
     *
     * @param  string  $path  Pfad der Datei innerhalb des Attachments-Speichers.
     * @param  string  $prettyName  Dateiname für den Download.
     * @return StreamedResponse Gestreamte Dateiantwort.
     */
    public function storage($path, $prettyName = '')
    {
        abort_unless(is_string($path), 404);
        $prettyName = is_string($prettyName) ? $prettyName : '';
        if (! Auth::check() && ! request()->hasValidSignature()) {
            abort(401);
        }
        if ((pathinfo($path, PATHINFO_EXTENSION) == '') && (pathinfo($prettyName, PATHINFO_EXTENSION) != '')) {
            $path .= '.'.pathinfo($prettyName, PATHINFO_EXTENSION);
        }
        $downloadName = $prettyName ? $this->normalizeDownloadFilename($prettyName) : basename($path);
        if (Storage::exists('attachments/'.$path)) {
            return Storage::download('attachments/'.$path, $downloadName);
        }
        abort(404);
    }

    /**
     * Zeigt ein Bild aus dem Attachments-Speicher an oder lädt es herunter.
     *
     * @param  Request  $request  Die aktuelle HTTP-Anfrage.
     * @param  string  $path  Pfad des Bildes innerhalb des Attachments-Speichers.
     * @param  string  $prettyName  Dateiname für den Download.
     * @return StreamedResponse Gestreamte Datei- bzw. Bildantwort.
     */
    public function image(Request $request, $path, $prettyName = '')
    {
        abort_unless(is_string($path), 404);
        $prettyName = is_string($prettyName) ? $prettyName : '';
        if (substr($path, 0, 12) == 'attachments/') {
            $path = substr($path, 12);
        }
        if ((pathinfo($path, PATHINFO_EXTENSION) == '') && (pathinfo($prettyName, PATHINFO_EXTENSION) != '')) {
            $path .= '.'.pathinfo($prettyName, PATHINFO_EXTENSION);
        }
        $prettyName = $prettyName ? FileHelper::normalizeFilename($prettyName) : basename($path);
        if (Storage::exists('attachments/'.$path)) {
            return $request->get('download', false) ? Storage::download('attachments/'.$path) : Storage::response('attachments/'.$path);
        }
        abort(404);
    }

    /**
     * Erzeugt einen QR-Code für den übergebenen Wert und gibt ihn als PNG aus.
     *
     * @param  string  $value  Inhalt des QR-Codes.
     * @param  string  $prettyName  Dateiname für den Download.
     * @return \Illuminate\Http\Response Antwort mit dem PNG-Bild.
     */
    public function qr($value, $prettyName = '')
    {
        $file = QRService::generate($value);
        $png = file_get_contents($file);
        unlink($file);
        abort_if($png === false, 500, 'Der QR-Code konnte nicht gelesen werden.');

        return response($png)->header('Content-Type', 'image/png');
    }

    /**
     * Erzeugt ein automatisches Attachment und gibt die Datei zum Download aus.
     *
     * @param  string  $type  Typ des zugehörigen Eintrags.
     * @param  int|string  $attachable  ID des zugehörigen Eintrags.
     * @param  string  $attachment  Schlüssel des automatischen Attachments.
     * @return \Symfony\Component\HttpFoundation\Response Dateiantwort des Attachments.
     */
    public function autoAttachment($type, $attachable, $attachment)
    {
        $generatedAttachment = AttachmentFactory::get($type, $attachment, $attachable);
        abort_unless($generatedAttachment instanceof AbstractAttachment, 404);

        return $generatedAttachment->download();
    }

    /**
     * Zeigt die Einrichtungsseite eines automatischen Attachments an.
     *
     * @param  string  $type  Typ des zugehörigen Eintrags.
     * @param  int|string  $attachable  ID des zugehörigen Eintrags.
     * @param  string  $attachment  Schlüssel des automatischen Attachments.
     * @return Response Inertia-Antwort mit der Einrichtungsseite.
     */
    public function autoAttachmentSetup($type, $attachable, $attachment)
    {
        /** @var BaptismLettersAddressLabelsAttachment $setupAttachment */
        $setupAttachment = AttachmentFactory::get($type, $attachment, $attachable);

        return $setupAttachment->setup();
    }

    /** Normalize a user supplied download filename to its scalar form. */
    private function normalizeDownloadFilename(string $filename): string
    {
        $normalized = FileHelper::normalizeFilename($filename);
        abort_unless(is_string($normalized), 500);

        return $normalized;
    }
}
