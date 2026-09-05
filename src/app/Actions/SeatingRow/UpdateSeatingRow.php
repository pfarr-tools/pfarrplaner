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

use App\Actions\AbstractUpdateAction;
use App\Contracts\SeatingRow\UpdatesSeatingRows;
use App\Events\Models\SeatingRow\UpdatedSeatingRow;
use App\Models\Location;
use App\Models\People\User;
use App\Models\Seating\SeatingRow;
use App\Models\Seating\SeatingSection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateSeatingRow extends AbstractUpdateAction implements UpdatesSeatingRows
{
    protected Location $location;

    public function redirectTo(): string
    {
        return route('admin.location.edit', ['modelId' => $this->location->id, 'tab' => 'seating']);
    }

    public function update(User $user, SeatingRow $seatingRow, array $input): SeatingRow
    {
        Gate::forUser($user)->authorize('update', $seatingRow);
        $input = Validator::make($input, SeatingRow::$validationRules)->validateWithBag('updateSeatingRow');

        $section = SeatingSection::findOrFail($input['seating_section_id']);
        $this->location = $section->location;
        Gate::forUser($user)->authorize('update', $this->location);

        $seatingRow->update(NormalizesSeatingRowData::normalize($input));
        UpdatedSeatingRow::dispatch($user, $seatingRow);
        $this->messages = ['success' => 'Die Sitzreihe wurde aktualisiert.'];

        return $seatingRow;
    }
}
