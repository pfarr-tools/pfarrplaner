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

namespace App\Reports;

use App\Mail\RegulatoryReportMail;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class RegulatoryReport extends AbstractReport
{
    /** @var string */
    public $title = 'Meldung an das Ordnungsamt';

    /**
     * @var string
     */
    public $group = 'Formulare';

    /**
     * @var string
     */
    public $description = 'Gottesdienst nach §1g Abs. 3 CoronaVO an das Ordnungsamt melden';

    public $icon = 'fa fa-envelope';

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function setup()
    {
        $preselectedService = \request()->get('service', null);
        $services = Service::inCities($this->authenticatedUser()->writableCities->pluck('id'))
            ->startingFrom(Carbon::now())
            ->ordered()
            ->limit(20)
            ->get();

        $recipient = $this->authenticatedUser()->getSetting('regulatory_email_address', '');
        $template = $this->authenticatedUser()->getSetting('regulatory_email_text', '') ?: View::make('reports.regulatory.template');
        Session::put('back', request()->headers->get('referer'));

        return $this->renderSetupView(compact('services', 'recipient', 'template', 'preselectedService'));
    }

    public function render(Request $request)
    {
        /** @var array{service: int, email: string, template: string, number?: int} $data */
        $data = $request->validate(
            [
                'service' => 'required|int|exists:services,id',
                'email' => 'required|email',
                'template' => 'required|string',
                'number' => 'int|min:1',
            ]
        );
        $user = $this->authenticatedUser();
        $service = Service::findOrFail($data['service']);
        if (! $user->can('update', $service)) {
            abort(401);
        }
        $user->setSetting('regulatory_email_address', $data['email']);
        $user->setSetting('regulatory_email_text', $data['template']);

        Mail::to($data['email'])
            ->bcc($this->authenticatedUser())
            ->queue(new RegulatoryReportMail($data));

        /** @var string $back */
        $back = Session::pull('back');

        return redirect()->to($back)->with('success', 'Deine Gottesdienstmeldung wurde an '.$data['email'].' gesendet.');
    }

    public function isActive(): bool
    {
        return false; // This report is currently not needed and therefore disabled.
    }
}
