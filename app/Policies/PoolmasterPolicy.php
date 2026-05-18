<?php

namespace App\Policies;

use App\Models\Leave\Poolmaster;
use App\Models\People\User;

class PoolmasterPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Poolmaster $poolmaster): bool
    {
        return true;
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
    public function update(User $user, Poolmaster $poolmaster): bool
    {
        if ($user->id === $poolmaster->user_id) {
            return true;
        }

        if ($user->hasPermissionTo('fremden-urlaub-bearbeiten')) {
            $cityIds = $poolmaster->user->homeCities->pluck('id');
            foreach ($user->writableCities as $city) {
                if ($cityIds->contains($city->id) && (!$poolmaster->user->hasRole('Pfarrer:in'))) {
                    return true;
                }
            }
        }

        if ($poolmaster->user->vacationAdmins->pluck('id')->contains($user->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Poolmaster $poolmaster): bool
    {
        return $this->update($user, $poolmaster);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Poolmaster $poolmaster): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Poolmaster $poolmaster): bool
    {
        //
    }
}
