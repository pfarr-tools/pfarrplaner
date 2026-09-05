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

namespace App\Actions\SeatingRow;

use App\Actions\AbstractDeleteAction;
use App\Contracts\SeatingRow\DeletesSeatingRows;
use App\Events\Models\SeatingRow\DeletedSeatingRow;
use App\Models\Location;
use App\Models\People\User;
use App\Models\Seating\SeatingRow;
use Illuminate\Support\Facades\Gate;

class DeleteSeatingRow extends AbstractDeleteAction implements DeletesSeatingRows
{
    protected Location $location;

    public function redirectTo(): string
    {
        return route('admin.location.edit', ['modelId' => $this->location->id, 'tab' => 'seating']);
    }

    public function delete(User $user, SeatingRow $seatingRow): bool
    {
        Gate::forUser($user)->authorize('delete', $seatingRow);

        $this->location = $seatingRow->seatingSection->location;
        $result = (bool) $seatingRow->delete();

        if ($result) {
            DeletedSeatingRow::dispatch($user, $seatingRow);
            $this->messages = ['success' => 'Die Sitzreihe wurde gelöscht.'];
        }

        return $result;
    }
}
