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

use App\Actions\AbstractUpdateAction;
use App\Contracts\Funeral\UpdatesFunerals;
use App\Events\Models\Funeral\UpdatedFuneral;
use App\Events\ServiceUpdated;
use App\Models\People\User;
use App\Models\Rites\Funeral;
use App\Models\Service;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateFuneral extends AbstractUpdateAction implements UpdatesFunerals
{
    use NormalizesFuneralData;

    protected string $redirectUrl = '';

    public function redirectTo(): string
    {
        return $this->redirectUrl ?: route('home');
    }

    /**
     * Aktualisiert die Beerdigung und die zugehörigen Dienste.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, Funeral $funeral, array $input): Funeral
    {
        Gate::forUser($user)->authorize('update', $funeral);

        $input = $this->prepareInputForValidation(array_merge([
            'service_id' => $funeral->service_id,
            'buried_name' => $funeral->buried_name,
        ], $input));
        /** @var int|string|null $serviceId */
        $serviceId = $input['service_id'] ?? null;
        $this->authorizeServiceWriteAccess($user, $serviceId);
        /** @var array<string, mixed> $validated */
        $validated = Validator::make($input, Funeral::$validationRules)->validateWithBag('updateFuneral');

        $funeral->update($this->normalizeValidatedInput($validated));
        if ($funeral->service) {
            $funeral->service->setDefaultOfferingValues();
            $funeral->service->save();
            ServiceUpdated::dispatch($funeral->service, $funeral->service->participants);
            $this->redirectUrl = route('service.edit', ['service' => $this->getServiceSlug($funeral->service), 'tab' => 'rites']);
        } else {
            $this->redirectUrl = route('home');
        }

        UpdatedFuneral::dispatch($user, $funeral);
        $this->messages = ['success' => 'Die Änderungen wurden gespeichert.'];

        return $funeral;
    }

    /**
     * @param  Service  $service  Service to derive the slug from.\n     * @return string
     */
    protected function getServiceSlug(Service $service): string
    {
        return $service->slug ?: $service->createSlug();
    }
}
