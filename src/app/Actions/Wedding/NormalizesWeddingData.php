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

use App\Models\People\User;
use App\Models\Rites\Wedding;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

trait NormalizesWeddingData
{
    /**
     * @param array $input
     * @return array
     */
    protected function prepareInputForValidation(array $input): array
    {
        if (isset($input['service']) && !isset($input['service_id'])) {
            $input['service_id'] = $input['service'];
        }

        $input['text'] ??= '';
        $input['docs_where'] ??= '';
        $input['registration_document'] ??= '';
        $input['registered'] ??= 0;
        $input['signed'] ??= 0;
        $input['docs_ready'] ??= 0;
        $input['notes'] ??= '';
        $input['music'] ??= '';
        $input['gift'] ??= '';
        $input['flowers'] ??= '';
        $input['docs_format'] ??= 0;
        $input['needs_permission'] ??= 0;
        $input['processed'] ??= 0;

        return $input;
    }

    /**
     * @param User $user
     * @param int|string|null $serviceId
     * @return void
     * @throws ValidationException
     */
    protected function authorizeServiceWriteAccess(User $user, int|string|null $serviceId): void
    {
        if (!empty($serviceId) && !$user->isAdmin) {
            $service = Service::find($serviceId);
            $allowed = $service && $user->writableCities->pluck('id')->contains((int)$service->city_id);
            if (!$allowed) {
                throw ValidationException::withMessages(['service_id' => 'Für diesen Gottesdienst fehlen Schreibrechte.']);
            }
        }
    }

    /**
     * @param array $input
     * @return array
     */
    protected function normalizeValidatedInput(array $input): array
    {
        $input['appointment'] = $this->parseDateTime($input['appointment'] ?? null);
        $input['spouse1_dob'] = $this->parseDate($input['spouse1_dob'] ?? null);
        $input['spouse1_dimissorial_requested'] = $this->parseDate($input['spouse1_dimissorial_requested'] ?? null);
        $input['spouse1_dimissorial_received'] = $this->parseDate($input['spouse1_dimissorial_received'] ?? null);
        $input['spouse2_dob'] = $this->parseDate($input['spouse2_dob'] ?? null);
        $input['spouse2_dimissorial_requested'] = $this->parseDate($input['spouse2_dimissorial_requested'] ?? null);
        $input['spouse2_dimissorial_received'] = $this->parseDate($input['spouse2_dimissorial_received'] ?? null);
        $input['permission_requested'] = $this->parseDate($input['permission_requested'] ?? null);
        $input['permission_received'] = $this->parseDate($input['permission_received'] ?? null);

        return $input;
    }

    /**
     * @param mixed $value
     * @return Carbon|null
     */
    protected function parseDate($value): ?Carbon
    {
        if (!$value) {
            return null;
        }
        if ($value instanceof Carbon) {
            return $value;
        }
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', (string)$value)) {
            return Carbon::createFromFormat('d.m.Y', $value);
        }

        return Carbon::parse($value);
    }

    /**
     * @param mixed $value
     * @return Carbon|null
     */
    protected function parseDateTime($value): ?Carbon
    {
        if (!$value) {
            return null;
        }
        if ($value instanceof Carbon) {
            return $value;
        }
        if (preg_match('/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}$/', (string)$value)) {
            return Carbon::createFromFormat('d.m.Y H:i', $value, 'Europe/Berlin')->setTimezone('UTC');
        }

        return Carbon::parse($value, 'Europe/Berlin')->setTimezone('UTC');
    }

    /**
     * @param Service $service
     * @return array
     */
    protected function draftDefaults(Service $service): array
    {
        return array_merge((new Wedding())->fillDefaults(), [
            'service_id' => $service->id,
        ]);
    }
}
