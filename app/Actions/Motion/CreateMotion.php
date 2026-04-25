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

use App\Actions\AbstractCreateAction;
use App\Contracts\Motion\CreatesMotions;
use App\Events\Models\Motion\CreatedMotion;
use App\Models\Meetings\Motion;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateMotion extends AbstractCreateAction implements CreatesMotions
{
    public function redirectTo(): string
    {
        return route('admin.antraege.index');
    }

    /**
     * @param User $user
     * @param array $input
     * @return Motion
     */
    public function create(User $user, array $input)
    {
        Gate::forUser($user)->authorize('create', Motion::class);
        $input = Validator::make($input, Motion::$validationRules)->validateWithBag('createMotion');
        $motion = Motion::create($input);
        CreatedMotion::dispatch($user, $motion);
        $this->messages = ['success' => 'Der neue Antrag wurde gespeichert.'];
        return $motion;
    }
}
