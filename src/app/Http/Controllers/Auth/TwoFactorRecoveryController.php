<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/** Render the isolated TOTP reenrolment page for recovered accounts. */
class TwoFactorRecoveryController extends Controller
{
    /** Show no profile props or authentication secrets on the recovery page. */
    public function show(): Response
    {
        return Inertia::render('Profile/TwoFactorRecovery');
    }
}
