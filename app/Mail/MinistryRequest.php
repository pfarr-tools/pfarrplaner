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

use App\Services\MinistryService;
use App\Models\People\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class MinistryRequest extends Mailable
{
    use Queueable, SerializesModels;

    /** @var User $user */
    protected $user;
    /** @var User $sender */
    protected $sender;

    protected $services;
    protected $ministry;
    protected $text = '';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $sender, $ministry, $services, $text)
    {
        $this->user = $user;
        $this->sender = $sender;
        $this->ministry = $ministry;
        $this->services = $services;
        $this->text = $text;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $urlData = [
            'ministry' => $this->ministry,
            'user' => (string)$this->user->id,
            'services' => $this->services->pluck('id')->join(','),
            'sender' => $this->sender->id,
        ];
        $url = URL::signedRoute('ministry.request', $urlData);

        $ministryTitle = MinistryService::all(true)[$this->ministry];


        return $this->subject('Anfrage: ' . $ministryTitle . ' im Gottesdienst')->markdown('mail.ministry.request')->with(
            [
                'user' => $this->user,
                'sender' => $this->sender,
                'ministry' => $ministryTitle,
                'services' => $this->services,
                'url' => $url,
                'text' => $this->text,
            ]
        );
    }
}
