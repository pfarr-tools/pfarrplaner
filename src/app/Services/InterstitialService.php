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
 *
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
     * @return array<int, array<string, mixed>>
     */
    public function forUser(User $user): array
    {
        // no interstitials in test environment, unless specifically enabled in url by ?show_interstitial=1
        if ((app()->environment('local', 'dusk')) && (! request()->boolean('show_interstitial'))) {
            return [];
        }

        $states = $this->statesForUser($user);
        $interstitials = [];

        /** @var array<string, array<string, mixed>> $items */
        $items = config('interstitials.items', []);

        foreach ($items as $key => $item) {
            if (! $this->isEnabled($item)) {
                continue;
            }

            if (! $this->matchesAudience($user, $item)) {
                continue;
            }

            $state = $states[$key] ?? [];
            if (($state['status'] ?? null) === self::ACTION_DISMISS
                && ($item['dismissible'] ?? true)) {
                continue;
            }

            $snoozedUntil = $state['snoozed_until'] ?? null;
            if (($item['repeatable'] ?? false)
                && ($state['status'] ?? null) === self::ACTION_LATER
                && (is_string($snoozedUntil) || $snoozedUntil instanceof \DateTimeInterface)
                && now()->lt($snoozedUntil)) {
                continue;
            }

            $details = $item['details'] ?? [];
            $interstitials[] = [
                'key' => $key,
                'level' => $item['level'] ?? 'info',
                'title' => $item['title'] ?? $key,
                'text' => $item['text'] ?? '',
                'details' => array_values(is_array($details) ? $details : []),
                'repeatable' => (bool) ($item['repeatable'] ?? false),
                'dismissible' => (bool) ($item['dismissible'] ?? true),
                'action_label' => $item['action_label'] ?? null,
                'action_url' => $item['action_url'] ?? null,
            ];
        }

        return $interstitials;
    }

    /**
     * Persist the user's reaction to an interstitial.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function remember(User $user, string $key, string $action): array
    {
        /** @var array<string, array<string, mixed>> $items */
        $items = config('interstitials.items', []);
        if (! isset($items[$key])) {
            throw ValidationException::withMessages([
                'key' => 'Dieses Interstitial ist nicht definiert.',
            ]);
        }

        if (! in_array($action, [self::ACTION_DISMISS, self::ACTION_LATER])) {
            throw ValidationException::withMessages([
                'action' => 'Ungültige Interstitial-Aktion.',
            ]);
        }

        if ($action === self::ACTION_DISMISS && ! ($items[$key]['dismissible'] ?? true)) {
            throw ValidationException::withMessages([
                'action' => 'Dieser Hinweis muss bis zur Einrichtung eines zusätzlichen Faktors wiederholt werden.',
            ]);
        }

        $states = $this->statesForUser($user);
        $states[$key] = [
            'status' => $action,
            'updated_at' => now()->toIso8601String(),
        ];
        if ($action === self::ACTION_LATER && ($items[$key]['repeatable'] ?? false)) {
            /** @var int $snoozeDays */
            $snoozeDays = $items[$key]['snooze_days'] ?? 21;
            $states[$key]['snoozed_until'] = now()->addDays(max(1, (int) $snoozeDays))->toIso8601String();
        }
        /** @var string $settingKey */
        $settingKey = config('interstitials.setting_key', 'interstitials');
        Settings::set($user, $settingKey, $states);

        return $states[$key];
    }

    /**
     * Get the stored interstitial state array for the user.
     *
     * @return array<string, array<string, mixed>>
     */
    public function statesForUser(User $user): array
    {
        /** @var string $settingKey */
        $settingKey = config('interstitials.setting_key', 'interstitials');
        $states = Settings::get($user, $settingKey);
        if (! is_array($states)) {
            return [];
        }
        /** @var array<string, array<string, mixed>> $states */

        return $states;
    }

    /**
     * Determine whether a configured interstitial is enabled.
     *
     * @param  array<string, mixed>  $item  Configured interstitial.
     */
    public function isEnabled(array $item): bool
    {
        return (bool) ($item['enabled'] ?? false);
    }

    /**
     * Check whether the current user belongs to the configured audience.
     *
     * @param  User  $user  User to check.
     * @param  array<string, mixed>  $item  Configured interstitial.
     */
    public function matchesAudience(User $user, array $item): bool
    {
        $audience = $item['audience'] ?? 'all';
        if (! is_array($audience)) {
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
                case 'no-extra-factor':
                    if (! $user->hasTwoFactorEnabled()
                        && ! $user->hasPendingTwoFactor()
                        && ! $user->passkeys()->exists()) {
                        return true;
                    }
                    break;
            }
        }

        return false;
    }
}
