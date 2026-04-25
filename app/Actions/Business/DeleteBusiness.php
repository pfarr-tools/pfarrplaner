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

use App\Actions\AbstractDeleteAction;
use App\Contracts\Business\DeletesBusinesses;
use App\Events\Models\Business\DeletedBusiness;
use App\Models\Meetings\Business;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;

class DeleteBusiness extends AbstractDeleteAction implements DeletesBusinesses
{
    public function redirectTo(): string
    {
        return route('admin.tops.index');
    }

    /**
     * @param User $user
     * @param Business $business
     * @return bool
     */
    public function delete(User $user, Business $business)
    {
        Gate::forUser($user)->authorize('delete', $business);
        DeletedBusiness::dispatch($user, $business);
        $this->messages = ['success' => 'Der Tagesordnungspunkt wurde gelöscht.'];
        return $business->delete();
    }
}
