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

namespace App\Services;

use App\Facades\Settings;
use App\Models\People\User;
use Illuminate\Validation\ValidationException;

class InterstitialService
{
    public const ACTION_DISMISS = 'dismiss';
    public const ACTION_LATER = 'later';

    /**
     * Resolve all configured interstitials that should currently be shown.
     *
     * @param User $user
     * @return array
     */
    public function forUser(User $user): array
    {
        $states = $this->statesForUser($user);
        $interstitials = [];

        foreach (config('interstitials.items', []) as $key => $item) {
            if (!$this->isEnabled($item)) {
                continue;
            }

            if (!$this->matchesAudience($user, $item)) {
                continue;
            }

            if (($states[$key]['status'] ?? null) === self::ACTION_DISMISS) {
                continue;
            }

            $interstitials[] = [
                'key' => $key,
                'level' => $item['level'] ?? 'info',
                'title' => $item['title'] ?? $key,
                'text' => $item['text'] ?? '',
                'details' => array_values($item['details'] ?? []),
            ];
        }

        return $interstitials;
    }

    /**
     * Persist the user's reaction to an interstitial.
     *
     * @param User $user
     * @param string $key
     * @param string $action
     * @return array
     * @throws ValidationException
     */
    public function remember(User $user, string $key, string $action): array
    {
        $items = config('interstitials.items', []);
        if (!isset($items[$key])) {
            throw ValidationException::withMessages([
                'key' => 'Dieses Interstitial ist nicht definiert.',
            ]);
        }

        if (!in_array($action, [self::ACTION_DISMISS, self::ACTION_LATER])) {
            throw ValidationException::withMessages([
                'action' => 'Ungültige Interstitial-Aktion.',
            ]);
        }

        $states = $this->statesForUser($user);
        $states[$key] = [
            'status' => $action,
            'updated_at' => now()->toIso8601String(),
        ];
        Settings::set($user, config('interstitials.setting_key', 'interstitials'), $states);

        return $states[$key];
    }

    /**
     * Get the stored interstitial state array for the user.
     *
     * @param User $user
     * @return array
     */
    public function statesForUser(User $user): array
    {
        $states = Settings::get($user, config('interstitials.setting_key', 'interstitials'), []);
        return is_array($states) ? $states : [];
    }

    /**
     * Determine whether a configured interstitial is enabled.
     *
     * @param array $item
     * @return bool
     */
    public function isEnabled(array $item): bool
    {
        return (bool)($item['enabled'] ?? false);
    }

    /**
     * Check whether the current user belongs to the configured audience.
     *
     * @param User $user
     * @param array $item
     * @return bool
     */
    public function matchesAudience(User $user, array $item): bool
    {
        $audience = $item['audience'] ?? 'all';
        if (!is_array($audience)) {
            $audience = [$audience];
        }

        foreach ($audience as $target) {
            switch ($target) {
                case 'all':
                    return true;
                case 'admin':
                    if ($user->isAdmin || $user->isLocalAdmin) {
                        return true;
                    }
                    break;
                case 'writer':
                    if ($user->writableCities->isNotEmpty()) {
                        return true;
                    }
                    break;
            }
        }

        return false;
    }
}
