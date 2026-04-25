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

namespace App\Actions\Business;

use App\Actions\AbstractUpdateAction;
use App\Contracts\Business\UpdatesBusinesses;
use App\Events\Models\Business\UpdatedBusiness;
use App\Models\Meetings\Business;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateBusiness extends AbstractUpdateAction implements UpdatesBusinesses
{
    public function redirectTo(): string
    {
        return route('admin.tops.index');
    }

    /**
     * @param User $user
     * @param Business $business
     * @param array $input
     * @return Business
     */
    public function update(User $user, Business $business, array $input)
    {
        Gate::forUser($user)->authorize('update', $business);
        $input = Validator::make($input, Business::$validationRules)->validateWithBag('updateBusiness');
        $business->update($input);
        UpdatedBusiness::dispatch($user, $business);
        $this->messages = ['success' => 'Der Tagesordnungspunkt wurde geändert.'];
        return $business;
    }
}
