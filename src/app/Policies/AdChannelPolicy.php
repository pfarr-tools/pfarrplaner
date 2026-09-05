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

use App\Models\Ads\AdChannel;
use App\Models\People\User;
use App\Models\Places\City;

class AdChannelPolicy
{
    /**
     * @param User $user
     * @return bool
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin || count($user->cities) > 0;
    }

    /**
     * @param User $user
     * @param AdChannel $adChannel
     * @return bool
     * Determine whether the user can view the model.
     */
    public function view(User $user, AdChannel $adChannel): bool
    {
        return $user->cities->pluck('id')->contains($adChannel->city_id);
    }

    /**
     * @param User $user
     * @param City|null $city
     * @return bool
     * Determine whether the user can create models.
     */
    public function create(User $user, ?City $city = null): bool
    {
        if (null === $city) {
            return count($user->writableCities) > 0;
        }
        return $user->writableCities->pluck('id')->contains($city->id);
    }

    /**
     * @param User $user
     * @param AdChannel $adChannel
     * @return bool
     * Determine whether the user can update the model.
     */
    public function update(User $user, AdChannel $adChannel): bool
    {
        return $user->writableCities->pluck('id')->contains($adChannel->city_id);
    }

    /**
     * @param User $user
     * @param AdChannel $adChannel
     * @return bool
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AdChannel $adChannel): bool
    {
        return $user->writableCities->pluck('id')->contains($adChannel->city_id);
    }

    /**
     * @param User $user
     * @param AdChannel $adChannel
     * @return bool
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AdChannel $adChannel): bool
    {
        return $user->writableCities->pluck('id')->contains($adChannel->city_id);
    }

    /**
     * @param User $user
     * @param AdChannel $adChannel
     * @return bool
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AdChannel $adChannel): bool
    {
        return $user->writableCities->pluck('id')->contains($adChannel->city_id);
    }
}
