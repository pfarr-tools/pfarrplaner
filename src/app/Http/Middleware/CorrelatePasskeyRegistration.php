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

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Correlate registration failures without adding credential payloads to logs. */
class CorrelatePasskeyRegistration
{
    /**
     * Attach a bounded correlation ID to the response and record failed server phases.
     *
     * @param  Request  $request  Registration request, whose payload is never logged here.
     * @param  Closure  $next  Remaining middleware including validation and challenge consumption.
     * @param  string  $phase  Server phase configured by the route.
     * @return Response Original response with a diagnostic ID header.
     */
    public function handle(Request $request, Closure $next, string $phase): Response
    {
        $suppliedId = $request->header('X-Passkey-Request-ID');
        $id = is_string($suppliedId) && Str::isUuid($suppliedId) ? $suppliedId : (string) Str::uuid();
        $response = $next($request);
        $response->headers->set('X-Passkey-Request-ID', $id);
        if ($response->getStatusCode() >= 400) {
            Log::warning('Passkey registration server failure', [
                'correlation_id' => $id,
                'phase' => $phase,
                'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
