<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 */

namespace App\Actions\Funeral;

use App\Actions\AbstractCreateAction;
use App\Contracts\Funeral\CreatesFunerals;
use App\Events\Models\Funeral\CreatedFuneral;
use App\Models\People\User;
use App\Models\Rites\Funeral;
use App\Models\Service;
use Illuminate\Support\Facades\Gate;

class CreateFuneral extends AbstractCreateAction implements CreatesFunerals
{
    use NormalizesFuneralData;

    public function redirectTo(): string
    {
        return '';
    }

    public function create(User $user, array $input): Funeral
    {
        $service = Service::findOrFail($input['service_id'] ?? $input['service'] ?? null);

        Gate::forUser($user)->authorize('create', [Funeral::class, $service]);

        $funeral = Funeral::create($this->draftDefaults($service));

        CreatedFuneral::dispatch($user, $funeral);
        $this->messages = ['success' => 'Die neue Bestattung wurde angelegt.'];

        return $funeral;
    }
}
