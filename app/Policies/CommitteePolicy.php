<?php

namespace App\Policies;

use App\Models\Meetings\Committee;
use App\Models\People\User;
use Illuminate\Auth\Access\Response;

class CommitteePolicy
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
    public function view(User $user, Committee $committee): bool
    {
        foreach ($committee->cities as $city) {
            if ($user->cities->pluck('id')->contains($city->id)) return true;
        }
        return false;
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
    public function update(User $user, Committee $committee): bool
    {
        foreach ($committee->cities as $city) {
            if ($user->writableCities->pluck('id')->contains($city->id)) return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Committee $committee): bool
    {
        foreach ($committee->cities as $city) {
            if ($user->writableCities->pluck('id')->contains($city->id)) return true;
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Committee $committee): bool
    {
        foreach ($committee->cities as $city) {
            if ($user->adminCities->pluck('id')->contains($city->id)) return true;
        }
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Committee $committee): bool
    {
        foreach ($committee->cities as $city) {
            if ($user->adminCities->pluck('id')->contains($city->id)) return true;
        }
        return false;
    }
}
