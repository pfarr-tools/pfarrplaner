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

use App\Models\People\User;
use App\Models\Rites\Funeral;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

trait NormalizesFuneralData
{
    /**
     * @param array $input
     * @return array
     */
    protected function prepareInputForValidation(array $input): array
    {
        foreach ([
            'buried_address',
            'buried_zip',
            'buried_city',
            'pronoun_set',
            'text',
            'type',
            'relative_name',
            'relative_address',
            'relative_zip',
            'relative_city',
            'wake_location',
            'spouse',
            'parents',
            'children',
            'further_family',
            'baptism',
            'confirmation',
            'undertaker',
            'eulogies',
            'notes',
            'announcements',
            'childhood',
            'profession',
            'family',
            'further_life',
            'faith',
            'events',
            'character',
            'death',
            'life',
            'attending',
            'quotes',
            'spoken_name',
            'professional_life',
            'birth_place',
            'death_place',
            'dimissorial_issuer',
            'birth_name',
            'appointment_address',
            'confirmation_text',
            'wedding_text',
        ] as $key) {
            $input[$key] ??= '';
        }

        $input['processed'] ??= 0;
        $input['needs_dimissorial'] ??= 0;
        $input['type'] = $input['type'] ?: 'Erdbestattung';

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
        $input['wake'] = $this->parseDate($input['wake'] ?? null);
        $input['dob'] = $this->parseDate($input['dob'] ?? null);
        $input['dod'] = $this->parseDate($input['dod'] ?? null);
        $input['announcement'] = $this->parseDate($input['announcement'] ?? null);
        $input['appointment'] = $this->parseDateTime($input['appointment'] ?? null);
        $input['dimissorial_requested'] = $this->parseDate($input['dimissorial_requested'] ?? null);
        $input['dimissorial_received'] = $this->parseDate($input['dimissorial_received'] ?? null);
        $input['baptism_date'] = $this->parseDate($input['baptism_date'] ?? null);
        $input['confirmation_date'] = $this->parseDate($input['confirmation_date'] ?? null);
        $input['wedding_date'] = $this->parseDate($input['wedding_date'] ?? null);
        $input['dod_spouse'] = $this->parseDate($input['dod_spouse'] ?? null);

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
        return array_merge((new Funeral())->fillDefaults(), [
            'service_id' => $service->id,
        ]);
    }
}
