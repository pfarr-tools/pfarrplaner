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
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Trait HandlesAttachmentsTrait
 */
trait HandlesAttachmentsTrait
{
    /**
     * Create, replace and remove attachments from a request.
     *
     * @param  mixed  $object  Model owning the attachments.
     * @return void
     */
    protected function handleAttachments(Request $request, $object)
    {
        /** @var Service $object */
        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            $descriptions = (array) $request->get('attachment_text', []);
            /** @var string $cut */
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
            /** @var string $uploadFromUrl */
            $uploadFromUrl = $request->get('uploadFromUrl');
            $path = 'attachments/'.Str::random(32).'.'.pathinfo($uploadFromUrl, PATHINFO_EXTENSION);
            Storage::put($path, (string) file_get_contents($uploadFromUrl));
            $description = $request->get('attachment_text') ?: 'ohne Beschreibung';
            $object->attachments()->create(['title' => $description, 'file' => $path]);
        } elseif ($request->has('uploadFromInbox')) {
            /** @var string $inboxPath */
            $inboxPath = config('inbox.path');
            /** @var string $uploadFromInbox */
            $uploadFromInbox = $request->get('uploadFromInbox');
            $description = $request->get('attachment_text') ?: 'ohne Beschreibung';
            $fileOrigin = storage_path($inboxPath.'/'.$uploadFromInbox);
            if (file_exists($fileOrigin)) {
                /** @var string $uploadFromUrl */
                $uploadFromUrl = $request->get('uploadFromUrl');
                $path = 'attachments/'.Str::random(32).'.'.pathinfo($uploadFromUrl, PATHINFO_EXTENSION);
                Storage::put($path, (string) file_get_contents($fileOrigin));
                unlink($fileOrigin);
                $object->attachments()->create(['title' => $description, 'file' => $path]);
            }
        }

        $this->removeAttachments($request, $object);
        $object->unsetRelation('attachments');
    }

    /**
     * Replace the single attachment assigned to a target cut.
     *
     * @param  mixed  $object  Model owning the attachments.
     * @param  string  $cut  Cut identifier.
     */
    protected function replaceAttachmentCut($object, string $cut): void
    {
        /** @var Service $object */
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
        /** @var Service $object */
        if ($request->has('remove_attachment')) {
            /** @var array<int, int|string> $attachmentIds */
            $attachmentIds = $request->get('remove_attachment');
            foreach ($attachmentIds as $attachmentId) {
                if (! method_exists($object, 'attachments')) {
                    continue;
                }
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
        /** @var Model $object */
        if ($request->hasFile($key)) {
            /** @var string $oldPath */
            $oldPath = $object->$key;
            // delete previous upload
            if ($oldPath != '') {
                Storage::delete($oldPath);
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
        /** @var Model $object */
        if ($request->has('remove_'.$key)) {
            /** @var string $storedPath */
            $storedPath = $object->$key;
            if (Storage::exists($storedPath)) {
                Storage::delete($storedPath);
            }
            $object->update([$key => '']);
        }
    }
}
