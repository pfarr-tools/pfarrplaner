<?php

namespace App\Policies;

use App\Models\Leave\Pool;
use App\Models\People\User;

class PoolPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Pfarrer:in') || $user->isAdmin;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function index(User $user): bool
    {
        return $user->hasRole('Pfarrer:in') || $user->isAdmin;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pool $pool): bool
    {
        return $user->hasRole('Pfarrer:in') || $user->isAdmin;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Pfarrer:in') || $user->isAdmin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pool $pool): bool
    {
        return $user->hasRole('Pfarrer:in') || $user->isAdmin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pool $pool): bool
    {
        return $user->hasRole('Pfarrer:in') || $user->isAdmin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Pool $pool): bool
    {
        return $user->hasRole('Pfarrer:in') || $user->isAdmin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Pool $pool): bool
    {
        return $user->hasRole('Pfarrer:in') || $user->isAdmin;
    }
}
