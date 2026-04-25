<?php

namespace App\Policies;

use App\Models\Ads\AdChannel;
use App\Models\People\User;

class AdChannelPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin || count($user->cities) > 0;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AdChannel $adChannel): bool
    {
        return $user->cities->pluck('id')->contains($adChannel->city_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AdChannel $adChannel): bool
    {
        return $user->writableCities->pluck('id')->contains($adChannel->city_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AdChannel $adChannel): bool
    {
        return $user->writableCities->pluck('id')->contains($adChannel->city_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AdChannel $adChannel): bool
    {
        return $user->writableCities->pluck('id')->contains($adChannel->city_id);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AdChannel $adChannel): bool
    {
        return $user->writableCities->pluck('id')->contains($adChannel->city_id);
    }
}
