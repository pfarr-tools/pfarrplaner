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

namespace App\Actions\Committee;

use App\Actions\AbstractDeleteAction;
use App\Contracts\Committee\DeletesCommittees;
use App\Events\Models\Committee\DeletedCommittee;
use App\Models\Meetings\Committee;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;

class DeleteCommittee extends AbstractDeleteAction implements DeletesCommittees
{
    public function redirectTo(): string
    {
        return route('admin.gremien.index');
    }

    /**
     * @param User $user
     * @param Committee $committee
     * @return bool
     */
    public function delete(User $user, Committee $committee)
    {
        Gate::forUser($user)->authorize('delete', $committee);
        DeletedCommittee::dispatch($user, $committee);
        $this->messages = ['success' => 'Das Gremium wurde gelöscht.'];
        return $committee->delete();
    }
}
