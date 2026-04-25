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

use App\Actions\AbstractUpdateAction;
use App\Contracts\Motion\UpdatesMotions;
use App\Events\Models\Motion\UpdatedMotion;
use App\Models\Meetings\Motion;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateMotion extends AbstractUpdateAction implements UpdatesMotions
{
    public function redirectTo(): string
    {
        return route('admin.antraege.index');
    }

    /**
     * @param User $user
     * @param Motion $motion
     * @param array $input
     * @return Motion
     */
    public function update(User $user, Motion $motion, array $input)
    {
        Gate::forUser($user)->authorize('update', $motion);
        $input = Validator::make($input, Motion::$validationRules)->validateWithBag('updateMotion');
        $motion->update($input);
        UpdatedMotion::dispatch($user, $motion);
        $this->messages = ['success' => 'Der Antrag wurde geändert.'];
        return $motion;
    }
}
