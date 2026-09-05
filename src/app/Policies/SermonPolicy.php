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
use App\Models\Sermon;
use Illuminate\Auth\Access\HandlesAuthorization;

class SermonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the sermon.
     *
     * A sermon is editable when the user may update each linked service.
     *
     * @param User $user
     * @param Sermon $sermon
     * @return bool
     */
    public function update(User $user, Sermon $sermon): bool
    {
        $sermon->loadMissing('services');

        if ($sermon->services->isEmpty()) {
            return false;
        }

        foreach ($sermon->services as $service) {
            if (!$user->can('update', $service)) {
                return false;
            }
        }

        return true;
    }
}
