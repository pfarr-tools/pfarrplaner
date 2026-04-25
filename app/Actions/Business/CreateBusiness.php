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

use App\Actions\AbstractCreateAction;
use App\Contracts\Business\CreatesBusinesses;
use App\Events\Models\Business\CreatedBusiness;
use App\Models\Meetings\Business;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateBusiness extends AbstractCreateAction implements CreatesBusinesses
{
    public function redirectTo(): string
    {
        return route('admin.tops.index');
    }

    /**
     * @param User $user
     * @param array $input
     * @return Business
     */
    public function create(User $user, array $input)
    {
        Gate::forUser($user)->authorize('create', Business::class);
        $input = Validator::make($input, Business::$validationRules)->validateWithBag('createBusiness');
        $business = Business::create($input);
        CreatedBusiness::dispatch($user, $business);
        $this->messages = ['success' => 'Der neue Tagesordnungspunkt wurde gespeichert.'];
        return $business;
    }
}
