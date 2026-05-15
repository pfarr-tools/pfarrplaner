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

namespace App\Actions\Wedding;

use App\Actions\AbstractCreateAction;
use App\Contracts\Wedding\CreatesWeddings;
use App\Events\Models\Wedding\CreatedWedding;
use App\Models\People\User;
use App\Models\Rites\Wedding;
use App\Models\Service;
use Illuminate\Support\Facades\Gate;

class CreateWedding extends AbstractCreateAction implements CreatesWeddings
{
    use NormalizesWeddingData;

    /**
     * @return string
     */
    public function redirectTo(): string
    {
        return '';
    }

    /**
     * @param User $user
     * @param array $input
     * @return Wedding
     */
    public function create(User $user, array $input): Wedding
    {
        $serviceId = $input['service_id'] ?? $input['service'] ?? null;
        $service = Service::findOrFail($serviceId);

        Gate::forUser($user)->authorize('create', [Wedding::class, $service]);

        $wedding = Wedding::create($this->draftDefaults($service));

        CreatedWedding::dispatch($user, $wedding);
        $this->messages = ['success' => 'Die neue Trauung wurde angelegt.'];

        return $wedding;
    }
}
