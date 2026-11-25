<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/** Keep calendar-feed-only Sanctum tokens out of the general JSON API. */
class RejectCalendarFeedToken
{
    /** Reject a feed-only credential after the API authentication middleware. */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken
            && in_array('calendar:feed', $token->abilities ?? [], true)) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
