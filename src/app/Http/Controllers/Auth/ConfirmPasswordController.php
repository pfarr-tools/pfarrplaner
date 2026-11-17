<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/** Confirms the authenticated user's password before sensitive account actions. */
class ConfirmPasswordController extends Controller
{
    /** Show the re-authentication form and preserve a safe local return path. */
    public function show(Request $request): InertiaResponse
    {
        return Inertia::render('Auth/ConfirmPassword', [
            'redirect' => $this->safeRedirect($request->query('redirect')),
        ]);
    }

    /**
     * Mark the session password-confirmed after verifying the current password.
     *
     * @param  Request  $request  The password-confirmation request.
     * @return JsonResponse|RedirectResponse A JSON result for modal requests or the traditional redirect.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate(['password' => ['required', 'string']]);
        $user = $request->user();

        if (! $user || ! Hash::check($validated['password'], $user->getAuthPassword())) {
            if ($request->expectsJson()) {
                throw ValidationException::withMessages([
                    'password' => 'Das Passwort ist nicht korrekt.',
                ]);
            }

            return back()->withErrors(['password' => 'Das Passwort ist nicht korrekt.']);
        }

        $request->session()->put('auth.password_confirmed_at', now()->timestamp);
        if ($request->expectsJson()) {
            return response()->json(['confirmed' => true]);
        }

        $redirect = $this->safeRedirect($request->input('redirect'));

        return $redirect
            ? redirect()->to($redirect)
            : redirect()->intended('/');
    }

    /** Reject external and protocol-relative confirmation return paths. */
    private function safeRedirect(mixed $target): ?string
    {
        if (! is_string($target)
            || ! str_starts_with($target, '/')
            || str_starts_with($target, '//')
            || str_contains($target, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $target)) {
            return null;
        }

        $parts = parse_url($target);
        if (! is_array($parts) || isset($parts['host']) || isset($parts['scheme'])) {
            return null;
        }

        return $target;
    }
}
