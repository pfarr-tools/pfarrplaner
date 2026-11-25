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

use App\Models\People\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

/** Controller for API token management. */
class TokenController extends Controller
{
    /**
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Render the token overview for the authenticated user.
     *
     * @return Response Inertia response with the user's tokens.
     */
    public function index(): Response
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        Gate::authorize('update', $user);
        $tokens = $user->tokens;
        $token = null;

        return Inertia::render('tokens', compact('tokens', 'token'));
    }

    /**
     * Create a new personal access token from the validated request.
     *
     * @param  Request  $request  Request containing the token title.
     * @return Response Inertia response with the new token and all tokens.
     */
    public function create(Request $request): Response
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        Gate::authorize('update', $user);
        $request->validate(['title' => 'required|string']);
        $token = $user->createToken($request->get('title'));
        $tokens = $user->tokens;

        return Inertia::render('tokens', compact('tokens', 'token'));
    }

    /**
     * Delete the given personal access token.
     *
     * @param  Request  $request  Incoming request.
     * @param  PersonalAccessToken  $token  Token to delete.
     * @return RedirectResponse Redirect to the token overview.
     */
    public function destroy(Request $request, PersonalAccessToken $token): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        Gate::authorize('update', $user);
        $user->tokens()->where('id', $token->id)->delete();

        return redirect()->route('tokens.index');
    }
}
