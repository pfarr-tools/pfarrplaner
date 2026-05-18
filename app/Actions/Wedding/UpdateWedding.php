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

use App\Actions\AbstractUpdateAction;
use App\Contracts\Wedding\UpdatesWeddings;
use App\Events\Models\Wedding\UpdatedWedding;
use App\Events\ServiceUpdated;
use App\Models\People\User;
use App\Models\Rites\Wedding;
use App\Models\Service;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateWedding extends AbstractUpdateAction implements UpdatesWeddings
{
    use NormalizesWeddingData;

    protected string $redirectUrl = '';

    /**
     * @return string
     */
    public function redirectTo(): string
    {
        return $this->redirectUrl ?: route('home');
    }

    /**
     * @param User $user
     * @param Wedding $wedding
     * @param array $input
     * @return Wedding
     */
    public function update(User $user, Wedding $wedding, array $input): Wedding
    {
        Gate::forUser($user)->authorize('update', $wedding);

        $input = $this->prepareInputForValidation(array_merge($wedding->only($wedding->getFillable()), $input));
        $this->authorizeServiceWriteAccess($user, $input['service_id'] ?? null);
        $validated = Validator::make($input, Wedding::$validationRules)->validateWithBag('updateWedding');

        $wedding->update($this->normalizeValidatedInput($validated));
        if ($wedding->service) {
            $wedding->service->setDefaultOfferingValues();
            $wedding->service->save();
            ServiceUpdated::dispatch($wedding->service, $wedding->service->participants);
            $this->redirectUrl = route('service.edit', ['service' => $this->getServiceSlug($wedding->service), 'tab' => 'rites']);
        } else {
            $this->redirectUrl = route('home');
        }

        UpdatedWedding::dispatch($user, $wedding);
        $this->messages = ['success' => 'Die Änderungen wurden gespeichert.'];

        return $wedding;
    }

    /**
     * @param Service $service
     * @return string
     */
    protected function getServiceSlug(Service $service): string
    {
        return $service->slug ?: $service->createSlug();
    }
}
