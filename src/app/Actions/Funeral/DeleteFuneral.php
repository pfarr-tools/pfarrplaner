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

namespace App\Actions\Funeral;

use App\Actions\AbstractDeleteAction;
use App\Contracts\Funeral\DeletesFunerals;
use App\Events\Models\Funeral\DeletedFuneral;
use App\Models\People\User;
use App\Models\Rites\Funeral;
use App\Models\Service;
use Illuminate\Support\Facades\Gate;

class DeleteFuneral extends AbstractDeleteAction implements DeletesFunerals
{
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
     * @param Funeral $funeral
     * @return bool|null
     */
    public function delete(User $user, Funeral $funeral): ?bool
    {
        Gate::forUser($user)->authorize('delete', $funeral);

        $redirect = $funeral->service
            ? route('service.edit', ['service' => $this->getServiceSlug($funeral->service), 'tab' => 'rites'])
            : route('home');
        $result = $funeral->delete();
        $this->redirectUrl = $redirect;

        DeletedFuneral::dispatch($user, $funeral);
        $this->messages = ['success' => 'Die Bestattung wurde gelöscht.'];

        return $result;
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
