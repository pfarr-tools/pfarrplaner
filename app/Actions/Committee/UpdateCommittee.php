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

use App\Actions\AbstractUpdateAction;
use App\Contracts\Committee\UpdatesCommittees;
use App\Events\Models\Committee\UpdatedCommittee;
use App\Models\Meetings\Committee;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateCommittee extends AbstractUpdateAction implements UpdatesCommittees
{
    public function redirectTo(): string
    {
        return route('admin.gremien.index');
    }

    /**
     * @param User $user
     * @param Committee $committee
     * @param array $input
     * @return Committee
     */
    public function update(User $user, Committee $committee, array $input)
    {
        Gate::forUser($user)->authorize('update', $committee);
        $input = Validator::make($input, Committee::$validationRules)->validateWithBag('updateCommittee');
        $committee->update($input);
        UpdatedCommittee::dispatch($user, $committee);
        $this->messages = ['success' => 'Das Gremium wurde geändert.'];
        return $committee;
    }
}
