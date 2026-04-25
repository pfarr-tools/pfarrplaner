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

use App\Actions\AbstractCreateAction;
use App\Contracts\Committee\CreatesCommittees;
use App\Events\Models\Committee\CreatedCommittee;
use App\Models\Meetings\Committee;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateCommittee extends AbstractCreateAction implements CreatesCommittees
{
    public function redirectTo(): string
    {
        return route('admin.gremien.index');
    }

    /**
     * @param User $user
     * @param array $input
     * @return Committee
     */
    public function create(User $user, array $input)
    {
        Gate::forUser($user)->authorize('create', Committee::class);
        $input = Validator::make($input, Committee::$validationRules)->validateWithBag('createCommittee');
        $committee = Committee::create($input);
        CreatedCommittee::dispatch($user, $committee);
        $this->messages = ['success' => 'Das neue Gremium wurde gespeichert.'];
        return $committee;
    }
}
