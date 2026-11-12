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

namespace App\Http\Controllers;

use App\Inputs\AbstractInput;
use App\Inputs\Inputs;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class InputController
 */
class InputController extends Controller
{
    /**
     * Protect input routes with authentication.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return Response
     */
    public function index()
    {
        $inputs = Inputs::all();

        return Inertia::render('Inputs/Index', compact('inputs'));
    }

    /**
     * @return Application|Factory|View
     */
    public function setup(Request $request, $input)
    {
        $input = $this->getInputClass($input);

        return $input->setup($request);
    }

    /**
     * Resolve and authorize an input handler.
     *
     * @param  string  $input  Input key.
     * @return AbstractInput Authorized input handler.
     */
    protected function getInputClass($input): AbstractInput
    {
        try {
            $input = Inputs::get($input);
        } catch (Exception $exception) {
            abort(404);
        }
        abort_unless($input->canEdit(), 403);

        return $input;
    }

    /**
     * Render an input's collected data page.
     *
     * @param  Request  $request  Input request.
     * @param  string  $input  Input key.
     * @return mixed Collected input response.
     */
    public function input(Request $request, $input)
    {
        $input = $this->getInputClass($input);

        return $input->input($request);
    }

    /**
     * Return to the input setup when a collected input page is reloaded.
     *
     * @param  string  $input  Input key.
     * @return RedirectResponse Setup redirect.
     */
    public function inputReload($input): RedirectResponse
    {
        return redirect()->route('inputs.setup', $input);
    }

    /**
     * Save data submitted by an input page.
     *
     * @param  Request  $request  Save request.
     * @param  string  $input  Input key.
     * @return mixed Save response.
     */
    public function save(Request $request, $input)
    {
        $input = $this->getInputClass($input);

        return $input->save($request);
    }
}
