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

/**
 * Created by PhpStorm.
 * User: Christoph Fischer
 * Date: 17.05.2019
 * Time: 16:51
 */

namespace App\Mail;

use App\Models\People\User;
use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class AbstractServiceMailable
 */
class AbstractServiceMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var Service */
    protected $service;

    /** @var array */
    protected $data = [];

    /** @var User */
    protected $user;

    /** @var Service */
    protected $original;

    /** @var User */
    protected $originatingUser;

    /**
     * @var array|mixed
     */
    protected $changed = [];

    /**
     * AbstractServiceMailable constructor.
     */
    public function __construct(User $user, Service $service, User $originatingUser, array $data)
    {
        $this->user = $user;
        $this->service = $service;
        $this->original = $service->originalObject;
        $this->changed = $service->changedFields;
        $this->data = $data;
        $this->originatingUser = $originatingUser;
    }
}
