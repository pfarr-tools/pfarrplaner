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

namespace App\Services;

use App\Models\People\User;
use Laravel\Sanctum\PersonalAccessToken;

/** Resolve a Sanctum or legacy API credential to its owning user. */
class ApiTokenAuthenticator
{
    /**
     * Find the user authenticated by a Sanctum personal token or legacy API token.
     *
     * Legacy values may be stored as plaintext or as SHA-256 hashes. Hashing the
     * supplied value on lookup preserves clients that hold the original token.
     *
     * @param  string|null  $token  Bearer credential received with the request.
     * @return User|null The authenticated user, or null when the token is invalid.
     */
    public static function findUser(?string $token): ?User
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        $user = User::query()
            ->where(function ($query) use ($token) {
                $query->where('api_token', $token)
                    ->orWhere('api_token', hash('sha256', $token));
            })
            ->first();

        if ($user) {
            return $user;
        }

        $personalAccessToken = PersonalAccessToken::findToken($token);
        $tokenable = $personalAccessToken?->tokenable;

        return $tokenable instanceof User ? $tokenable : null;
    }
}
