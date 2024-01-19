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

namespace App\Http\Requests;

use App\Models\Leave\Absence;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

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
        if (!$this->has('id')) {
            return false;
        }
        if (null === $this->absence) {
            $this->absence = Absence::find($this->get('id'));
        }
        return $this->user()->can('update', $this->absence);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'from' => 'required|date_format:d.m.Y',
            'to' => 'required|date_format:d.m.Y',
            'reason' => 'required|string',
            'replacement_notes' => 'nullable|string',
            'workflow_status' => 'int|in:' . Absence::STATUS_NEW,
            'sick_days' => 'nullable|bool',
            'internal_notes' => 'nullable|string',
        ];
        if ($this->route()->getName() == 'absences.store') {
            $rules['user_id'] = 'required|exists:users,id';
        }

        if (null === $this->absence) {
            $this->absence = Absence::find($this->get('id'));
        }
        // self-administrator?
        if ($this->user()->can('selfAdminister', $this->absence)) {
            $rules['workflow_status'] = 'nullable|int|in:' . join(
                    ',',
                    [
                        Absence::STATUS_SELF_ADMINISTERED,
                        Absence::STATUS_SELF_ADMINISTERED_AND_APPROVED
                    ]
                );
            $rules['approved_at'] = 'nullable|date_format:d.m.Y';
        } else {
            $mayCheck = $this->absence->user->vacationAdmins->pluck('id')->contains($this->user()->id);
            $mayApprove = $this->absence->user->vacationApprovers->pluck('id')->contains($this->user()->id);
            if ($mayCheck) {
                $rules['workflow_status'] = 'nullable|int|in:' . join(
                        ',',
                        [Absence::STATUS_NEW, Absence::STATUS_CHECKED]
                    );
                $rules['admin_notes'] = 'nullable|string';
                $rules['admin_id'] = 'nullable|int|exists:users,id';
            }
            if ($mayApprove) {
                $rules['workflow_status'] = 'nullable|int|in:' . join(
                        ',',
                        [
                            Absence::STATUS_NEW,
                            Absence::STATUS_CHECKED,
                            Absence::STATUS_APPROVED
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
     * @return array|void
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        $data['from'] = Carbon::createFromFormat('d.m.Y', $data['from'])->setTime(0, 0, 0);
        $data['to'] = Carbon::createFromFormat('d.m.Y', $data['to'])->setTime(23, 59, 59);

        if (isset($data['approved_at'])) {
            if (strlen($data['approved_at']) == 10) $data['approved_at'] .= ' 0:00:00';
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
            if ($data['workflow_status'] >= Absence::STATUS_CHECKED && (null === $this->absence->admin_id)) {
                $data['admin_id'] = $this->user()->id;
                $data['checked_at'] = Carbon::now();
            }
            if ($data['workflow_status'] == Absence::STATUS_APPROVED && (null === $this->absence->approver_id)) {
                $data['approver_id'] = $this->user()->id;
                $data['approved_at'] = Carbon::now();
            }
        }
        return $data;
    }

    /**
     * @return Absence
     */
    public function getAbsence(): ?Absence
    {
        return $this->absence;
    }

    /**
     * @param Absence $absence
     */
    public function setAbsence(?Absence $absence): void
    {
        $this->absence = $absence;
    }


}
