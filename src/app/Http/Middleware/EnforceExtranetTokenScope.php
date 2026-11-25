<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 */

namespace App\Http\Middleware;

use App\Models\People\User;
use App\Services\ExtranetTokenScope;
use App\Services\RoleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces Sanctum ability and permission boundaries on Extranet routes.
 */
class EnforceExtranetTokenScope
{
    /**
     * Validate calendar-feed rejection and church-scoped Extranet token constraints.
     *
     * @param  Request  $request  Current HTTP request.
     * @param  Closure(Request): Response  $next  Next middleware in the pipeline.
     * @return Response Authorized downstream response or an HTTP 401/403 JSON error.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (ExtranetTokenScope::isCalendarFeedToken($token)) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        if (! ExtranetTokenScope::isScopedExtranetToken($token)) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        if (! str_starts_with($routeName, 'extranet.services.manage.')) {
            return response()->json(
                ['message' => 'Dieser API-Schlüssel ist auf die Extranet-Veranstaltungs-API beschränkt.'],
                Response::HTTP_FORBIDDEN
            );
        }

        if (! $user instanceof User || ! $user->can(RoleService::PERMISSION_CREATE_CHURCH_API_TOKENS)) {
            return response()->json(
                ['message' => 'Keine Berechtigung für Gemeinde-API-Schlüssel.'],
                Response::HTTP_FORBIDDEN
            );
        }

        if (ExtranetTokenScope::effectiveWritableCityIds($user, $token)->isEmpty()) {
            return response()->json(
                ['message' => 'Keine Schreibberechtigung für die zugeordnete Kirchengemeinde.'],
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
