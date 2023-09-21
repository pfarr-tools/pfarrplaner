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

namespace App\Mail;

use App\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class RegulatoryReportMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $data = [];
    protected $user = null;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $user = null)
    {
        $this->data = $data;
        $this->user = $user ?? Auth::user();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $service = Service::find($this->data['service']);
        $sender = 'Evangelische Kirchengemeinde '.$service->city->name.'';
        $text = strtr(
            $this->data['template'],
            [
                '###GOTTESDIENST###' => join(', ',
                                             [
                                                 $service->date->format('d.m.Y'),
                                                 $service->timeText(),
                                                 $service->city->name,
                                                 $service->locationText(),
                                             ]
                    ).($service->titleText() != 'GD' ? ', '.$service->titleText() : ''),
                '###TEILNEHMERZAHL###' => $this->data['number'],
                '###KIRCHENGEMEINDE###' => $sender,
            ]
        );

        $user = $this->user;

        return $this->from('noreply@pfarrplaner.de', $sender)
            ->replyTo($user)
            ->subject('Gottesdienstmeldung nach §1g Abs. 3 CoronaVO')
            ->markdown('reports.regulatory.mail', compact('text', 'user', 'sender'));
    }
}
