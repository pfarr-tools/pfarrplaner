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
 */

namespace App\Policies;

use App\Models\People\User;
use App\Models\Rites\Wedding;
use App\Models\Service;
use Illuminate\Auth\Access\HandlesAuthorization;

class WeddingPolicy
{
    use HandlesAuthorization;

    public function index(User $user): bool
    {
        return $this->create($user);
    }

    protected function hasServicePermission(User $user, ?Service $service): bool
    {
        if ($service === null) {
            return false;
        }

        return $user->isAdmin || $user->writableCities->pluck('id')->contains($service->city_id);
    }

    public function create(User $user, ?Service $service = null): bool
    {
        if (! $user->hasPermissionTo('gd-bearbeiten')) {
            return false;
        }

        return ($service === null) || $this->hasServicePermission($user, $service);
    }

    public function update(User $user, ?Wedding $wedding = null): bool
    {
        return $user->hasPermissionTo('gd-bearbeiten')
            && ($wedding !== null)
            && $this->hasServicePermission($user, $wedding->service);
    }

    public function delete(User $user, ?Wedding $wedding = null): bool
    {
        return $this->update($user, $wedding);
    }

    public function restore(User $user, ?Wedding $wedding = null): bool
    {
        return $this->delete($user, $wedding);
    }

    public function forceDelete(User $user, ?Wedding $wedding = null): bool
    {
        return $this->delete($user, $wedding);
    }
}
