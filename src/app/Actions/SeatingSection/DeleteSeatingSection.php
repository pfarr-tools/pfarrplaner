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

namespace App\Actions\SeatingSection;

use App\Actions\AbstractDeleteAction;
use App\Contracts\SeatingSection\DeletesSeatingSections;
use App\Events\Models\SeatingSection\DeletedSeatingSection;
use App\Models\Location;
use App\Models\People\User;
use App\Models\Seating\SeatingSection;
use Illuminate\Support\Facades\Gate;

class DeleteSeatingSection extends AbstractDeleteAction implements DeletesSeatingSections
{
    protected Location $location;

    public function redirectTo(): string
    {
        return route('admin.location.edit', ['modelId' => $this->location->id, 'tab' => 'seating']);
    }

    public function delete(User $user, SeatingSection $seatingSection): bool
    {
        Gate::forUser($user)->authorize('delete', $seatingSection);

        $this->location = $seatingSection->location;
        $result = (bool) $seatingSection->delete();

        if ($result) {
            DeletedSeatingSection::dispatch($user, $seatingSection);
            $this->messages = ['success' => 'Der Sitzbereich wurde gelöscht.'];
        }

        return $result;
    }
}
