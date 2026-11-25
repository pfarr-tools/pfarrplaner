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

namespace App\Inputs;

use App\Models\Calendar\Day;
use App\Models\Places\City;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Class AbstractInput
 */
class AbstractInput
{
    /**
     * @var string
     */
    public $title = '';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var string
     */
    /** @var string */
    protected $setupView = 'inputs.setup';

    public function canEdit(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getInputViewName()
    {
        return $this->getViewName('input');
    }

    /**
     * @param  string  $viewName  View name suffix.
     * @return string
     */
    public function getViewName($viewName)
    {
        return 'inputs.'.strtolower($this->getKey()).'.'.$viewName;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return lcfirst(
            strtr(
                get_called_class(),
                [
                    'Input' => '',
                    'App\\Inputs\\' => '',
                ]
            )
        );
    }

    /**
     * @param  City  $city  City the values are collected for.
     * @param  array<array-key, mixed>  $days  Days to collect values for.
     * @return array<string, mixed> Collected input values.
     */
    public function getValues(City $city, $days)
    {
        return [];
    }

    /**
     * @return Application|Factory|View
     */
    public function setup(Request $request)
    {
        $minDate = Day::orderBy('date', 'asc')->limit(1)->first();
        $maxDate = Day::orderBy('date', 'desc')->limit(1)->first();
        $user = Auth::user();
        abort_unless($user !== null, 403);
        $cities = $user->writableCities;

        /** @var view-string $setupView */
        $setupView = $this->setupView;

        return view(
            $setupView,
            [
                'input' => $this,
                'minDate' => $minDate,
                'maxDate' => $maxDate,
                'cities' => $cities,
            ]
        );
    }

    /**
     * @param  Request  $request  Input request.
     * @return mixed Input response.
     */
    public function input(Request $request) {}

    /**
     * @param  Request  $request  Save request.
     * @return mixed Save response.
     */
    public function save(Request $request) {}
}
