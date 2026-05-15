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

namespace App\Actions\Attachment;

use App\Actions\AbstractUpdateAction;
use App\Contracts\Attachment\UpdatesAttachments;
use App\Events\Models\Attachment\UpdatedAttachment;
use App\Models\Attachment;
use App\Models\People\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateAttachment extends AbstractUpdateAction implements UpdatesAttachments
{
    public function redirectTo(): string
    {
        return '';
    }

    public function update(User $user, Attachment $attachment, array $input): Attachment
    {
        Gate::forUser($user)->authorize('update', $attachment);

        $files = $input['attachments'] ?? [];
        $cut = $input['cut'] ?? null;

        foreach ($files as $file) {
            if (!($file instanceof UploadedFile)) {
                continue;
            }

            if ($cut) {
                $path = $file->storeAs(
                    'attachments',
                    'zuschnitt-' . $attachment->attachable_id . '-' . $cut . '.' . $file->getClientOriginalExtension()
                );
            } else {
                $path = $file->storeAs('attachments', Str::random(32) . '.' . $file->getClientOriginalExtension());
            }

            if (($attachment->file !== $path) && Storage::exists($attachment->file)) {
                Storage::delete($attachment->file);
            }

            $attachment->update([
                'file' => $path,
                'cut' => $cut,
            ]);
        }

        $attachment->refresh();
        UpdatedAttachment::dispatch($user, $attachment);
        $this->messages = ['success' => 'Die Datei wurde aktualisiert.'];

        return $attachment;
    }
}
