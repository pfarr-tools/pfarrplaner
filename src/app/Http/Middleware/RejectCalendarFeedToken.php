<?php

namespace App\Http\Middleware;

use App\Services\ExtranetTokenScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Keep scoped calendar-feed and church Extranet Sanctum tokens out of the general JSON API. */
class RejectCalendarFeedToken
{
    /** Reject a restricted credential after the API authentication middleware. */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (ExtranetTokenScope::isCalendarFeedToken($token)
            || ExtranetTokenScope::isScopedExtranetToken($token)) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
