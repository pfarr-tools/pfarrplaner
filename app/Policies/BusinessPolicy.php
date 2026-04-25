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

use App\Models\Meetings\Business;
use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Auth\Access\HandlesAuthorization;

class BusinessPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin || $user->isLocalAdmin;
    }

    public function view(User $user, Business $business): bool
    {
        return $user->isAdmin || $user->isLocalAdmin;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin || $user->isLocalAdmin;
    }

    public function update(User $user, Business $business): bool
    {
        return $user->isAdmin || $user->isLocalAdmin;
    }

    public function delete(User $user, Business $business): bool
    {
        return $user->isAdmin || $user->isLocalAdmin;
    }

    public function restore(User $user, Business $business): bool
    {
        return $user->isAdmin;
    }

    public function forceDelete(User $user, Business $business): bool
    {
        return $user->isAdmin;
    }
}
