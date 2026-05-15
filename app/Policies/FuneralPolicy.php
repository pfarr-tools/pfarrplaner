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
 */

namespace App\Policies;

use App\Models\People\User;
use App\Models\Rites\Funeral;
use App\Models\Service;
use Illuminate\Auth\Access\HandlesAuthorization;

class FuneralPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return bool
     */
    public function index(User $user): bool
    {
        return $this->create($user);
    }

    /**
     * @param User $user
     * @param Service|null $service
     * @return bool
     */
    protected function hasServicePermission(User $user, ?Service $service): bool
    {
        if (null === $service) {
            return false;
        }

        return $user->isAdmin || $user->writableCities->pluck('id')->contains($service->city_id);
    }

    /**
     * @param User $user
     * @param Service|null $service
     * @return bool
     */
    public function create(User $user, ?Service $service = null): bool
    {
        if (!$user->hasPermissionTo('gd-bearbeiten')) {
            return false;
        }

        return (null === $service) || $this->hasServicePermission($user, $service);
    }

    /**
     * @param User $user
     * @param Funeral|null $funeral
     * @return bool
     */
    public function update(User $user, ?Funeral $funeral = null): bool
    {
        return $user->hasPermissionTo('gd-bearbeiten')
            && (null !== $funeral)
            && $this->hasServicePermission($user, $funeral->service);
    }

    /**
     * @param User $user
     * @param Funeral|null $funeral
     * @return bool
     */
    public function delete(User $user, ?Funeral $funeral = null): bool
    {
        return $this->update($user, $funeral);
    }
}
