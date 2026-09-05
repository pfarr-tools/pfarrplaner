<?php

namespace App\Policies;

use App\Models\Liturgy\Text;
use App\Models\People\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TextPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine whether the user can view the index of all texts.
     *
     * @param User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }


    /**
     * Determine whether the user can view the text.
     *
     * @param User $user
     * @param Text $text
     * @return mixed
     */
    public function view(User $user, Text $text)
    {
        return true;
    }

    /**
     * Determine whether the user can create texts.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the text.
     *
     * @param User $user
     * @param Text $text
     * @return mixed
     */
    public function update(User $user, Text $text)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the text.
     *
     * @param User $user
     * @param Text $text
     * @return mixed
     */
    public function delete(User $user, Text $text)
    {
        return true;
    }

    /**
     * Determine whether the user can restore the text.
     *
     * @param User $user
     * @param Text $text
     * @return mixed
     */
    public function restore(User $user, Text $text)
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the text.
     *
     * @param User $user
     * @param Text $text
     * @return mixed
     */
    public function forceDelete(User $user, Text $text)
    {
        return true;
    }

}
