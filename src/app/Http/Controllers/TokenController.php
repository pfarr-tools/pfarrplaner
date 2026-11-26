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
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
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
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $rawTitle = $validated['title'] ?? '';
        $title = is_string($rawTitle) ? $rawTitle : '';
        $rawCityId = $validated['city_id'] ?? null;
        $cityId = is_int($rawCityId) || (is_string($rawCityId) && is_numeric($rawCityId))
            ? (int) $rawCityId
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
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
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
            ? ExtranetTokenScope::directlyWritableCityIds($user)->all()
            : [];

        $churchTokenCities = $writableCityIds === []
            ? []
            : City::query()
                ->whereIn('id', $writableCityIds)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (City $city): array => [
                    'id' => $city->id,
                    'name' => $city->name,
                ])
                ->values()
                ->all();

        $rawTokens = $user->tokens()->orderByDesc('created_at')->get();
        /** @var list<PersonalAccessToken> $personalTokens */
        $personalTokens = [];
        /** @var list<int> $referencedCityIds */
        $referencedCityIds = [];
        foreach ($rawTokens as $item) {
            if (! $item instanceof PersonalAccessToken) {
                continue;
            }
            $personalTokens[] = $item;
            foreach (ExtranetTokenScope::allowedCityIds($item) ?? [] as $referencedCityId) {
                $referencedCityIds[] = $referencedCityId;
            }
        }
        $referencedCityIds = array_values(array_unique($referencedCityIds));

        /** @var array<int, string> $cityNamesById */
        $cityNamesById = [];
        if ($referencedCityIds !== []) {
            foreach (City::query()->whereIn('id', $referencedCityIds)->get(['id', 'name']) as $city) {
                $cityNamesById[$city->id] = $city->name;
            }
        }

        $tokens = array_map(
            static fn (PersonalAccessToken $item): array => ExtranetTokenScope::describeToken($item, $cityNamesById),
            $personalTokens
        );

        return Inertia::render('tokens', [
            'tokens' => $tokens,
            'token' => $token,
            'canCreateChurchTokens' => $canCreateChurchTokens,
            'churchTokenCities' => $churchTokenCities,
        ]);
    }
}
