<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
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

namespace App\Http\Controllers\Auth\DGM;

use App\Http\Controllers\Controller;
use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DGMAuthController extends Controller
{
    private const DISCOVERY_CACHE_KEY = 'msft_oidc_discovery_organizations_v2';
    private const JWKS_CACHE_KEY = 'msft_oidc_jwks_organizations_v2';

    private function discovery(): array
    {
        return Cache::remember(self::DISCOVERY_CACHE_KEY, 3600, function () {
            $tenant = 'organizations'; // Work/School Accounts (multi-tenant)
            $url = "https://login.microsoftonline.com/{$tenant}/v2.0/.well-known/openid-configuration";

            $res = Http::timeout(10)->get($url);
            $res->throw();

            return $res->json();
        });
    }

    public function redirect()
    {
        $state = Str::random(40);
        $nonce = Str::random(40);

        session([
                    'msft_oauth_state' => $state,
                    'msft_oauth_nonce' => $nonce,
                ]);

        $d = $this->discovery();

        $params = [
            'client_id' => config('services.microsoft.client_id'),
            'response_type' => 'code',
            'redirect_uri' => config('services.microsoft.redirect'),
            'response_mode' => 'query',
            'scope' => 'openid profile email',
            'state' => $state,
            'nonce' => $nonce,
            // optional:
            // 'prompt' => 'select_account',
        ];

        return redirect()->away($d['authorization_endpoint'] . '?' . http_build_query($params));
    }

    public function callback(Request $request)
    {
        // Microsoft kann Fehler als Query-Params zurückgeben
        if ($request->filled('error')) {
            abort(
                403,
                'Microsoft login error: ' . $request->string(
                    'error_description',
                    $request->string('error')
                )->toString()
            );
        }

        // state prüfen (CSRF-Schutz)
        $expectedState = session('msft_oauth_state');
        session()->forget('msft_oauth_state');

        if (!$expectedState || !hash_equals($expectedState, (string)$request->query('state'))) {
            abort(403, 'Invalid state.');
        }

        $code = (string)$request->query('code');
        if ($code === '') {
            abort(400, 'Missing authorization code.');
        }

        $d = $this->discovery();

        // Code gegen Tokens tauschen
        $tokenRes = Http::asForm()->timeout(10)->post($d['token_endpoint'], [
            'client_id' => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('services.microsoft.redirect'),
            'scope' => 'openid profile email',
        ]);

        $tokenRes->throw();
        $token = $tokenRes->json();

        $idToken = $token['id_token'] ?? null;
        if (!$idToken) {
            abort(403, 'Missing id_token.');
        }

        // ID Token prüfen (Signatur + essentielle Claims)
        $claims = $this->validateIdToken($idToken, $d);
        dd($claims);

        // Tenant lock: nur ELKW
        $elkwTid = config('services.microsoft.elkw_tenant_id');
        if (!$elkwTid || ($claims['tid'] ?? null) !== $elkwTid) {
            abort(403, 'Dieses Microsoft-Konto gehört nicht zur ELKW.');
        }

        // Login-ID: bevorzugt UPN / preferred_username, fallback email
        $login = $claims['preferred_username'] ?? $claims['email'] ?? null;
        if (!$login) {
            abort(403, 'Keine E-Mail/UPN im Token gefunden.');
        }

        $login = Str::lower($login);

        // Zusätzlicher Domain-Check (wenn du wirklich nur @elkw.de willst)
        if (!Str::endsWith($login, '@elkw.de')) {
            abort(403, 'Bitte ein @elkw.de Konto verwenden.');
        }

        // Existing user only
        $user = User::whereRaw('LOWER(email) = ?', [$login])->first();
        if (!$user) {
            abort(403, 'Zu dieser Adresse existiert in der App kein Konto.');
        }

        Auth::login($user, remember: true);

        return redirect()->intended('/');
    }

    private function validateIdToken(string $jwt, array $discovery): array
    {
        $nonceExpected = session('msft_oauth_nonce');
        session()->forget('msft_oauth_nonce');

        // JWT Header (kid)
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            abort(403, 'Malformed id_token.');
        }

        $header = json_decode(JWT::urlsafeB64Decode($parts[0]), true) ?: [];
        $kid = $header['kid'] ?? null;

        // JWKS laden
        $jwksUri = $discovery['jwks_uri'] ?? null;
        if (!$jwksUri) {
            abort(500, 'Discovery missing jwks_uri.');
        }

        $jwks = Cache::remember(self::JWKS_CACHE_KEY, 3600, function () use ($jwksUri) {
            $res = Http::timeout(10)->get($jwksUri);
            $res->throw();
            return $res->json();
        });

        $keys = JWK::parseKeySet($jwks);
        $key = ($kid && isset($keys[$kid])) ? $keys[$kid] : null;

        // Falls kid nicht gefunden (Rotation): einmal frisch versuchen
        if (!$key) {
            Cache::forget(self::JWKS_CACHE_KEY);
            $jwks = Cache::remember(self::JWKS_CACHE_KEY, 60, function () use ($jwksUri) {
                $res = Http::timeout(10)->get($jwksUri);
                $res->throw();
                return $res->json();
            });

            $keys = JWK::parseKeySet($jwks);
            $key = ($kid && isset($keys[$kid])) ? $keys[$kid] : null;
        }

        if (!$key) {
            abort(403, 'Cannot validate token signature (kid not found).');
        }

        // Decode + Signaturprüfung
        $decoded = JWT::decode($jwt, $key);
        $claims = json_decode(json_encode($decoded), true);

        // aud prüfen
        if (($claims['aud'] ?? null) !== config('services.microsoft.client_id')) {
            abort(403, 'Invalid token audience.');
        }

        // exp prüfen (JWT lib prüft exp i. d. R. automatisch beim decode nicht; wir prüfen explizit)
        $now = time();
        if (isset($claims['exp']) && (int)$claims['exp'] < $now) {
            abort(403, 'Token expired.');
        }

        // nonce prüfen (Schutz gegen Replay)
        if ($nonceExpected && (($claims['nonce'] ?? null) !== $nonceExpected)) {
            abort(403, 'Invalid nonce.');
        }

        // iss grob plausibilisieren
        // (v2 issuer enthält typischerweise /{tid}/v2.0)
        if (!isset($claims['iss']) || !is_string($claims['iss']) || !Str::contains(
                $claims['iss'],
                'https://login.microsoftonline.com/'
            )) {
            abort(403, 'Invalid token issuer.');
        }

        return $claims;
    }
}
