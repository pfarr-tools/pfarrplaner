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

namespace App\Actions\Motion;

use App\Actions\AbstractDeleteAction;
use App\Contracts\Motion\DeletesMotions;
use App\Events\Models\Motion\DeletedMotion;
use App\Models\Meetings\Motion;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;

class DeleteMotion extends AbstractDeleteAction implements DeletesMotions
{
    public function redirectTo(): string
    {
        return route('admin.antraege.index');
    }

    /**
     * @param User $user
     * @param Motion $motion
     * @return bool
     */
    public function delete(User $user, Motion $motion)
    {
        Gate::forUser($user)->authorize('delete', $motion);
        DeletedMotion::dispatch($user, $motion);
        $this->messages = ['success' => 'Der Antrag wurde gelöscht.'];
        return $motion->delete();
    }
}
