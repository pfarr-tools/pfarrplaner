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

namespace App\Policies;

use App\Models\Attachment;
use App\Models\People\User;
use App\Models\Service;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttachmentPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param Attachment $attachment
     * @return bool
     */
    public function update(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->resolveAttachableIncludingTrashed();

        if (!$attachable) {
            return false;
        }

        if (method_exists($attachable, 'trashed') && $attachable->trashed()) {
            return false;
        }

        $service = null;
        if ($attachable instanceof Service) {
            $service = $attachable;
        } elseif (method_exists($attachable, 'service')) {
            $service = $attachable->service;
            if ((null === $service) && isset($attachable->service_id) && $attachable->service_id) {
                $service = Service::withTrashed()->find($attachable->service_id);
            }
        }

        if ($service && method_exists($service, 'trashed') && $service->trashed()) {
            return false;
        }

        return $user->can('update', $attachable);
    }
}
