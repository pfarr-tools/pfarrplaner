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
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Prevent an administrator proxy session from changing a user's login factors. */
class PreventImpersonatedSecurityChanges
{
    /**
     * Reject factor-management requests made through an impersonated account.
     *
     * @param  Request  $request  Current HTTP request.
     * @param  Closure  $next  Next middleware in the request pipeline.
     * @return Response The downstream response for a direct user session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->session()->has('adminUserSwitchBack'), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
