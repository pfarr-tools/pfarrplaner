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
 *
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

namespace App\Http\Requests;

use App\Models\Leave\Absence;
use App\Models\People\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AbsenceRequest extends FormRequest
{
    /** @var Absence absence */
    protected $absence = null;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $routeName = $this->route() ? $this->route()->getName() : null;
        $absence = $this->resolveAbsence();

        if ($absence === null) {
            return false;
        }

        if ($routeName === 'absence.store') {
            return Gate::forUser($this->user())->check('create', [Absence::class, $absence]);
        }

        return $this->user()->can('update', $absence);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'from' => ['required', $this->dateValueRule()],
            'to' => ['required', $this->dateValueRule()],
            'reason' => 'required|string',
            'replacement_notes' => 'nullable|string',
            'workflow_status' => 'int|in:'.Absence::STATUS_NEW,
            'sick_days' => 'nullable|bool',
            'internal_notes' => 'nullable|string',
        ];
        if ($this->route()->getName() == 'absence.store') {
            $rules['user_id'] = 'required|exists:users,id';
        }

        $this->resolveAbsence();
        if ($this->absence === null) {
            return $rules;
        }

        // self-administrator?
        if ($this->user()->can('selfAdminister', $this->absence)) {
            $rules['workflow_status'] = 'nullable|int|in:'.implode(
                ',',
                [
                    Absence::STATUS_SELF_ADMINISTERED,
                    Absence::STATUS_SELF_ADMINISTERED_AND_APPROVED,
                ]
            );
            $rules['approved_at'] = 'nullable|date_format:d.m.Y';
        } else {
            $mayCheck = $this->absence->user->vacationAdmins->pluck('id')->contains($this->user()->id);
            $mayApprove = $this->absence->user->vacationApprovers->pluck('id')->contains($this->user()->id);
            if ($mayCheck) {
                $rules['workflow_status'] = 'nullable|int|in:'.implode(
                    ',',
                    [Absence::STATUS_NEW, Absence::STATUS_CHECKED]
                );
                $rules['admin_notes'] = 'nullable|string';
                $rules['admin_id'] = 'nullable|int|exists:users,id';
            }
            if ($mayApprove) {
                $rules['workflow_status'] = 'nullable|int|in:'.implode(
                    ',',
                    [
                        Absence::STATUS_NEW,
                        Absence::STATUS_CHECKED,
                        Absence::STATUS_APPROVED,
                    ]
                );
                $rules['approver_notes'] = 'nullable|string';
                $rules['approver_id'] = 'nullable|int|exists:users,id';
            }
        }

        return $rules;
    }

    /**
     * Get the validated data
     *
     * @return array|void
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        $data['from'] = $this->normalizePlannerDate($data['from']);
        $data['to'] = $this->normalizePlannerDate($data['to'], true);

        if (isset($data['approved_at'])) {
            if (strlen($data['approved_at']) == 10) {
                $data['approved_at'] .= ' 0:00:00';
            }
            $data['approved_at'] = Carbon::parse($data['approved_at']);
        }

        // check if admin/approver id needs to be set
        if (isset($data['workflow_status']) && ($this->absence->workflow_status != $data['workflow_status'])) {
            if ($data['workflow_status'] == Absence::STATUS_NEW) {
                $data['admin_id'] = null;
                $data['approver_id_id'] = null;
                $data['checked_at'] = null;
                $data['approved_at'] = null;
            }
            if ($data['workflow_status'] >= Absence::STATUS_CHECKED && ($this->absence->admin_id === null)) {
                $data['admin_id'] = $this->user()->id;
                $data['checked_at'] = Carbon::now();
            }
            if ($data['workflow_status'] == Absence::STATUS_APPROVED && ($this->absence->approver_id === null)) {
                $data['approver_id'] = $this->user()->id;
                $data['approved_at'] = Carbon::now();
            }
        }

        return $data;
    }

    /**
     * Resolve the absence that is being stored or updated.
     */
    protected function resolveAbsence(): ?Absence
    {
        if ($this->absence !== null) {
            return $this->absence;
        }

        if ($this->has('id')) {
            $this->absence = Absence::find($this->get('id'));

            return $this->absence;
        }

        if (($this->route() ? $this->route()->getName() : null) !== 'absence.store') {
            return null;
        }

        $user = User::find($this->get('user_id'));
        if ($user === null) {
            return null;
        }

        $this->absence = new Absence([
            'user_id' => $user->id,
            'workflow_status' => Absence::STATUS_NEW,
        ]);
        $this->absence->setRelation('user', $user);

        return $this->absence;
    }

    /**
     * Validate a planner date value in ISO or legacy d.m.Y format.
     */
    protected function dateValueRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->parsePlannerDate($value) === null) {
                $fail('Das Feld '.$attribute.' muss ein gueltiges Datum sein.');
            }
        };
    }

    /**
     * Normalize a planner date to a UTC calendar day.
     */
    protected function normalizePlannerDate(mixed $value, bool $endOfDay = false): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('/T.*(?:Z|[+\-]\d{2}:\d{2})$/', $value)) {
            return Carbon::parse($value)->setTimezone('UTC');
        }

        $date = $this->parsePlannerDate($value);
        if ($date === null) {
            return null;
        }

        return $date
            ->copy()
            ->setTimezone('Europe/Berlin')
            ->setTime($endOfDay ? 23 : 0, $endOfDay ? 59 : 0, $endOfDay ? 59 : 0)
            ->setTimezone('UTC');
    }

    /**
     * Parse a planner date from ISO or legacy d.m.Y input.
     */
    protected function parsePlannerDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $value)) {
            return Carbon::createFromFormat('d.m.Y', $value, 'Europe/Berlin');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value, 'Europe/Berlin');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m-d H:i:s', $value, 'Europe/Berlin');
        }

        try {
            return Carbon::parse($value)->setTimezone('Europe/Berlin');
        } catch (\Throwable) {
            return null;
        }
    }

    public function getAbsence(): ?Absence
    {
        return $this->absence;
    }

    public function setAbsence(?Absence $absence): void
    {
        $this->absence = $absence;
    }
}
