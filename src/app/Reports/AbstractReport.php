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

use App\Models\People\User;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Class AbstractReport
 */
class AbstractReport
{
    /**
     * @var string
     */
    public $icon = 'fa fa-file';

    /**
     * @var string
     */
    public $title = '';

    /**
     * @var string
     */
    public $group = '';

    /**
     * @var string
     */
    public $description = '';

    /** @var bool */
    protected $inertia = false;

    /**
     * Return the user who is running this report.
     *
     * @throws AuthenticationException When the report is requested without an authenticated user.
     */
    protected function authenticatedUser(): User
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }

    /**
     * Ensure a date parsed from validated report input is usable.
     *
     * @param  Carbon|false|null  $date  Result from Carbon::createFromFormat().
     * @return Carbon Parsed date.
     *
     * @throws InvalidArgumentException When the input cannot be parsed.
     */
    protected function requireParsedDate(Carbon|false|null $date): Carbon
    {
        if (! $date instanceof Carbon) {
            throw new InvalidArgumentException('Das eingegebene Datum konnte nicht verarbeitet werden.');
        }

        return $date;
    }

    /**
     * Returns true if the report is active for the current user
     */
    public function isActive(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getRenderViewName()
    {
        return $this->getViewName('render');
    }

    /**
     * @param  string  $view  View name suffix.
     * @return string
     */
    public function getViewName(string $view)
    {
        $viewName = sprintf('reports.%s.%s', strtolower($this->getKey()), strtolower($view));

        return $viewName;
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
                    'Report' => '',
                    'App\\Reports\\' => '',
                ]
            )
        );
    }

    /**
     * @return string
     */
    public function render(Request $request)
    {
        return '';
    }

    /**
     * @param  array<string, mixed>  $data  Variables passed to the render view.
     * @return \Illuminate\Contracts\View\View
     */
    public function renderSetupView(array $data = [])
    {
        $data = array_merge(['report' => $this->getKey()], $data);

        return ViewFacade::file(ViewFacade::getFinder()->find($this->getSetupViewName()), $data);
    }

    /**
     * @return string
     */
    public function getSetupViewName()
    {
        return $this->getViewName('setup');
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function setup()
    {
        return $this->renderView('setup');
    }

    /**
     * @param  string  $view  View name suffix.
     * @param  array<string, mixed>  $data  Variables passed to the render view.
     * @return \Illuminate\Contracts\View\View
     */
    public function renderView(string $view, array $data = [])
    {
        $data = array_merge(['report' => $this->getKey()], $data);

        return ViewFacade::file(ViewFacade::getFinder()->find($this->getViewName($view)), $data);
    }

    public function isInertia(): bool
    {
        return $this->inertia;
    }

    /**
     * @param  bool  $inertia  Whether the report uses Inertia.\n     * @return void
     */
    public function setInertia(bool $inertia): void
    {
        $this->inertia = $inertia;
    }
}
