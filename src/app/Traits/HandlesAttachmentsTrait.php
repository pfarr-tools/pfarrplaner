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

namespace App\Traits;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Trait HandlesAttachmentsTrait
 */
trait HandlesAttachmentsTrait
{
    protected function handleAttachments(Request $request, $object)
    {
        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            $descriptions = (array) $request->get('attachment_text', []);
            $cut = $request->get('cut');
            if (count($files) === 1 && $cut) {
                $this->replaceAttachmentCut($object, $cut);
            }
            foreach ($files as $key => $file) {
                $path = $file->storeAs('attachments', Str::random(32).'.'.$file->getClientOriginalExtension());
                $description = ($descriptions[$key] ?? null) ?: ucfirst(pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                ));
                Attachment::create([
                    'title' => $description,
                    'file' => $path,
                    'cut' => $cut,
                    'attachable_id' => $object->id,
                    'attachable_type' => $object->getMorphClass(),
                ]);
            }
        } elseif ($request->has('uploadFromUrl')) {
            $path = 'attachments/'.Str::random(32).'.'.pathinfo($request->get('uploadFromUrl'), PATHINFO_EXTENSION);
            Storage::put($path, file_get_contents($request->get('uploadFromUrl')));
            $description = $request->get('attachment_text') ?: 'ohne Beschreibung';
            $object->attachments()->create(['title' => $description, 'file' => $path]);
        } elseif ($request->has('uploadFromInbox')) {
            $description = $request->get('attachment_text') ?: 'ohne Beschreibung';
            $fileOrigin = storage_path(config('inbox.path').'/'.$request->get('uploadFromInbox'));
            if (file_exists($fileOrigin)) {
                $path = 'attachments/'.Str::random(32).'.'.pathinfo($request->get('uploadFromUrl'), PATHINFO_EXTENSION);
                Storage::put($path, file_get_contents($fileOrigin));
                unlink($fileOrigin);
                $object->attachments()->create(['title' => $description, 'file' => $path]);
            }
        }

        $this->removeAttachments($request, $object);
        $object->unsetRelation('attachments');
    }

    /** Replace the single attachment assigned to a target cut. */
    protected function replaceAttachmentCut($object, string $cut): void
    {
        $object->attachments()->where('cut', $cut)->get()->each(function (Attachment $attachment): void {
            if (Attachment::where('file', $attachment->file)->where('id', '<>', $attachment->id)->doesntExist()) {
                Storage::delete($attachment->file);
            }
            $attachment->delete();
        });
    }

    /**
     * Remove attachments selected by the user and clean up shared storage files.
     *
     * @param  Request  $request  Current attachment request.
     * @param  object  $object  Attachment owner.
     * @return void
     */
    protected function removeAttachments(Request $request, $object)
    {
        if ($request->has('remove_attachment')) {
            foreach ($request->get('remove_attachment') as $attachmentId) {
                $attachment = $object->attachments()->findOrFail($attachmentId);
                if (Attachment::where('file', $attachment->file)->count() == 1) {
                    Storage::delete($attachment->file);
                }
                $object->attachments()->where('id', $attachmentId)->delete();
                $attachment->delete();
            }
        }
    }

    /**
     * Store or replace an attachment held in a model attribute.
     *
     * @param  Request  $request  Current attachment request.
     * @param  object  $object  Attachment owner.
     * @param  string  $key  Model attribute containing the stored path.
     * @return void
     */
    protected function handleIndividualAttachment(Request $request, $object, $key)
    {
        if ($request->hasFile($key)) {
            // delete previous upload
            if ($object->$key != '') {
                Storage::delete($object->$key);
            }
            $file = $request->file($key);
            $path = $file->store('attachments');
            $object->update([$key => $path]);
        }

        $this->removeIndividualAttachment($request, $object, $key);
    }

    /**
     * Remove an attachment held in a model attribute when requested.
     *
     * @param  Request  $request  Current attachment request.
     * @param  object  $object  Attachment owner.
     * @param  string  $key  Model attribute containing the stored path.
     * @return void
     */
    protected function removeIndividualAttachment(Request $request, $object, $key)
    {
        if ($request->has('remove_'.$key)) {
            if (Storage::exists($object->$key)) {
                Storage::delete($object->$key);
            }
            $object->update([$key => '']);
        }
    }
}
