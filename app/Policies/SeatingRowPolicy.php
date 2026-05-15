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
use App\Models\Seating\SeatingRow;
use Illuminate\Auth\Access\HandlesAuthorization;

class SeatingRowPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('Super-Administrator:in') ? true : null;
    }

    public function index(User $user): bool
    {
        return count($user->writableCities) > 0;
    }

    public function viewAny(User $user): bool
    {
        return $this->index($user);
    }

    public function create(User $user): bool
    {
        return count($user->writableCities) > 0;
    }

    public function update(User $user, SeatingRow $seatingRow): bool
    {
        return $user->can('update', $seatingRow->seatingSection->location);
    }

    public function delete(User $user, SeatingRow $seatingRow): bool
    {
        return $user->can('delete', $seatingRow->seatingSection->location);
    }
}
