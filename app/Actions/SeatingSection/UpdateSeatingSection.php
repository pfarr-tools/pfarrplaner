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

use App\Actions\AbstractUpdateAction;
use App\Contracts\SeatingSection\UpdatesSeatingSections;
use App\Events\Models\SeatingSection\UpdatedSeatingSection;
use App\Models\Location;
use App\Models\People\User;
use App\Models\Seating\SeatingSection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateSeatingSection extends AbstractUpdateAction implements UpdatesSeatingSections
{
    protected Location $location;

    public function redirectTo(): string
    {
        return route('admin.location.edit', ['modelId' => $this->location->id, 'tab' => 'seating']);
    }

    public function update(User $user, SeatingSection $seatingSection, array $input): SeatingSection
    {
        Gate::forUser($user)->authorize('update', $seatingSection);
        $input = Validator::make($input, SeatingSection::$validationRules)->validateWithBag('updateSeatingSection');

        $this->location = Location::findOrFail($input['location_id']);
        Gate::forUser($user)->authorize('update', $this->location);

        $input = NormalizesSeatingSectionData::normalize($input);

        $seatingSection->update($input);
        UpdatedSeatingSection::dispatch($user, $seatingSection);
        $this->messages = ['success' => 'Der Sitzbereich wurde aktualisiert.'];

        return $seatingSection;
    }
}
