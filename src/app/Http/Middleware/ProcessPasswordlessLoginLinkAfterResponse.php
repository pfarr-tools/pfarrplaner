<?php

namespace App\Http\Middleware;

use App\Jobs\ProcessPasswordlessLoginLinkRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Send a passwordless login link only after its generic response was sent. */
class ProcessPasswordlessLoginLinkAfterResponse
{
    /** Continue the request without doing account-dependent work. */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /** Process a validated login-link request after the HTTP response is sent. */
    public function terminate(Request $request, Response $response): void
    {
        $email = $request->attributes->get('passwordless_login_link_email');
        if (is_string($email)) {
            ProcessPasswordlessLoginLinkRequest::dispatchSync($email);
        }
    }
}
