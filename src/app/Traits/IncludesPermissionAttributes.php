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

namespace App\Traits;

use Auth;

/**
 * Exposes the current user's permissions on a model as boolean attributes.
 */
trait IncludesPermissionAttributes
{
    /**
     * Determines whether the authenticated user may show this model.
     *
     * @return bool True when the user is allowed to show the model.
     */
    public function getIsShowableAttribute(): bool
    {
        if (Auth::guest()) {
            return false;
        }

        return Auth::user()->can('show', $this);
    }

    /**
     * Determines whether the authenticated user may edit this model.
     *
     * @return bool True when the user is allowed to update the model.
     */
    public function getIsEditableAttribute(): bool
    {
        if (Auth::guest()) {
            return false;
        }

        return Auth::user()->can('update', $this);
    }

    /**
     * Determines whether the authenticated user may delete this model.
     *
     * @return bool True when the user is allowed to delete the model.
     */
    public function getIsDeletableAttribute(): bool
    {
        if (Auth::guest()) {
            return false;
        }

        return Auth::user()->can('delete', $this);
    }
}
