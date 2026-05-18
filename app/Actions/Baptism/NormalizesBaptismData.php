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
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Actions\Baptism;

use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Rites\Baptism;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

trait NormalizesBaptismData
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

        if (!isset($input['city_id']) && !empty($input['service_id'])) {
            $service = Service::find($input['service_id']);
            if ($service) {
                $input['city_id'] = $service->city_id;
            }
        }

        $input['candidate_address'] ??= '';
        $input['candidate_zip'] ??= '';
        $input['candidate_city'] ??= '';
        $input['candidate_phone'] ??= '';
        $input['candidate_email'] ??= '';
        $input['first_contact_with'] ??= '';
        $input['docs_where'] ??= '';
        $input['docs_ready'] ??= 0;
        $input['signed'] ??= 0;
        $input['registered'] ??= 0;
        $input['text'] ??= '';
        $input['notes'] ??= '';
        $input['processed'] ??= 0;
        $input['needs_dimissorial'] ??= 0;
        $input['dimissorial_issuer'] ??= '';
        $input['birth_place'] ??= '';

        return $input;
    }

    /**
     * @param User $user
     * @param int|string|null $cityId
     * @return void
     * @throws ValidationException
     */
    protected function authorizeCityWriteAccess(User $user, int|string|null $cityId): void
    {
        if (!empty($cityId) && !$user->isAdmin) {
            $allowed = $user->writableCities->pluck('id')->contains((int) $cityId);
            if (!$allowed) {
                throw ValidationException::withMessages(['city_id' => 'Für diese Kirchengemeinde fehlen Schreibrechte.']);
            }
        }
    }

    /**
     * @param array $input
     * @return array
     */
    protected function normalizeValidatedInput(array $input): array
    {
        $input['dob'] = $this->parseDate($input['dob'] ?? null);
        $input['first_contact_on'] = $this->parseDate($input['first_contact_on'] ?? null);
        $input['dimissorial_requested'] = $this->parseDate($input['dimissorial_requested'] ?? null);
        $input['dimissorial_received'] = $this->parseDate($input['dimissorial_received'] ?? null);
        $input['appointment'] = $this->parseDateTime($input['appointment'] ?? null);

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
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', (string) $value)) {
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
        if (preg_match('/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}$/', (string) $value)) {
            return Carbon::createFromFormat('d.m.Y H:i', $value, 'Europe/Berlin')->setTimezone('UTC');
        }
        return Carbon::parse($value, 'Europe/Berlin')->setTimezone('UTC');
    }

    /**
     * @param User $user
     * @param Service|null $service
     * @return array
     */
    protected function draftDefaults(User $user, ?Service $service): array
    {
        $draftCityId = $service?->city_id ?: $user->writableCities->first()?->id ?: City::query()->value('id');

        return [
            'candidate_name' => '',
            'candidate_address' => '',
            'candidate_zip' => '',
            'candidate_city' => '',
            'candidate_email' => '',
            'candidate_phone' => '',
            'first_contact_with' => '',
            'appointment' => now(),
            'registered' => 0,
            'signed' => 0,
            'docs_ready' => 0,
            'docs_where' => '',
            'service_id' => $service?->id,
            'city_id' => $draftCityId,
            'text' => '',
            'notes' => '',
            'processed' => 0,
        ];
    }
}
