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

use App\Http\Requests\StorePersonalTokenRequest;
use App\Models\People\User;
use App\Models\Places\City;
use App\Services\ExtranetTokenScope;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Manages personal and church-scoped Sanctum API tokens for the authenticated user.
 */
class TokenController extends Controller
{
    /**
     * Require an authenticated web session for token management routes.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Render the token overview page for the authenticated user.
     *
     * @return Response Inertia token management page.
     */
    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();
        Gate::authorize('update', $user);

        return $this->renderTokensPage($user, null);
    }

    /**
     * Issue a personal or church-scoped Sanctum token and display its plain-text secret once.
     *
     * @param  StorePersonalTokenRequest  $request  Validated token creation request.
     * @return Response Inertia token management page including the newly issued token.
     */
    public function create(StorePersonalTokenRequest $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $title = (string) $validated['title'];
        $cityId = isset($validated['city_id']) && $validated['city_id'] !== ''
            ? (int) $validated['city_id']
            : null;

        $abilities = $cityId !== null
            ? ExtranetTokenScope::abilitiesForCity($cityId)
            : ['*'];

        $newToken = $user->createToken($title, $abilities);

        return $this->renderTokensPage($user, $newToken);
    }

    /**
     * Revoke an existing Sanctum token owned by the authenticated user.
     *
     * @param  Request  $request  Authenticated deletion request.
     * @param  PersonalAccessToken  $token  Token selected for revocation.
     * @return RedirectResponse Redirect back to the token overview.
     */
    public function destroy(Request $request, PersonalAccessToken $token): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        Gate::authorize('update', $user);
        $user->tokens()->where('id', $token->id)->delete();

        return redirect()->route('tokens.index');
    }

    /**
     * Build the Inertia response payload for the token management view.
     *
     * @param  User  $user  Current user whose tokens are displayed.
     * @param  NewAccessToken|null  $token  Newly created token to display once, if any.
     * @return Response Rendered Inertia response.
     */
    private function renderTokensPage(User $user, ?NewAccessToken $token): Response
    {
        $canCreateChurchTokens = $user->can(RoleService::PERMISSION_CREATE_CHURCH_API_TOKENS);
        $writableCityIds = $canCreateChurchTokens
            ? ExtranetTokenScope::directlyWritableCityIds($user)
            : collect();

        $churchTokenCities = $writableCityIds->isEmpty()
            ? []
            : City::query()
                ->whereIn('id', $writableCityIds->all())
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (City $city): array => [
                    'id' => (int) $city->id,
                    'name' => (string) $city->name,
                ])
                ->values()
                ->all();

        $rawTokens = $user->tokens()->orderByDesc('created_at')->get();
        $referencedCityIds = $rawTokens
            ->flatMap(static fn (PersonalAccessToken $item): array => ExtranetTokenScope::allowedCityIds($item) ?? [])
            ->unique()
            ->values();

        $cityNamesById = $referencedCityIds->isEmpty()
            ? collect()
            : City::query()
                ->whereIn('id', $referencedCityIds->all())
                ->pluck('name', 'id')
                ->mapWithKeys(static fn (mixed $name, mixed $id): array => [(int) $id => (string) $name]);

        $tokens = $rawTokens
            ->map(static fn (PersonalAccessToken $item): array => ExtranetTokenScope::describeToken($item, $cityNamesById))
            ->values()
            ->all();

        return Inertia::render('tokens', [
            'tokens' => $tokens,
            'token' => $token,
            'canCreateChurchTokens' => $canCreateChurchTokens,
            'churchTokenCities' => $churchTokenCities,
        ]);
    }
}
