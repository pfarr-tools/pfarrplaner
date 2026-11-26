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

namespace App\Services;

use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use Illuminate\Support\Collection;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Resolves and formats church-scoped Sanctum token abilities for Extranet service management.
 */
class ExtranetTokenScope
{
    public const ABILITY_CALENDAR_FEED = 'calendar:feed';

    public const ABILITY_SERVICES = 'extranet:services';

    public const ABILITY_CITY_PREFIX = 'extranet:city:';

    /**
     * Build the Sanctum abilities array for a church-scoped Extranet service token.
     *
     * @param  int  $cityId  Target church identifier.
     * @return list<string> Abilities restricting the token to Extranet services of that church.
     */
    public static function abilitiesForCity(int $cityId): array
    {
        return [
            self::ABILITY_SERVICES,
            self::ABILITY_CITY_PREFIX.$cityId,
        ];
    }

    /**
     * Determine whether the access token is restricted to the iCal calendar feed.
     *
     * @param  object|null  $token  Current access token on the authenticated user.
     * @return bool True when the token carries the calendar-feed ability.
     */
    public static function isCalendarFeedToken(?object $token): bool
    {
        return $token instanceof PersonalAccessToken
            && in_array(self::ABILITY_CALENDAR_FEED, $token->abilities ?? [], true);
    }

    /**
     * Determine whether the access token is a church-scoped Extranet service credential.
     *
     * @param  object|null  $token  Current access token on the authenticated user.
     * @return bool True when the token is scoped to Extranet services without wildcard access.
     */
    public static function isScopedExtranetToken(?object $token): bool
    {
        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        $abilities = $token->abilities ?? [];
        if (in_array('*', $abilities, true)) {
            return false;
        }

        if (in_array(self::ABILITY_SERVICES, $abilities, true)) {
            return true;
        }

        foreach ($abilities as $ability) {
            if (is_string($ability) && str_starts_with($ability, self::ABILITY_CITY_PREFIX)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the church IDs permitted by a scoped Extranet token, or null when unrestricted.
     *
     * @param  object|null  $token  Current access token on the authenticated user.
     * @return list<int>|null Allowed church IDs for scoped tokens, or null for sessions/wildcard tokens.
     */
    public static function allowedCityIds(?object $token): ?array
    {
        if (! $token instanceof PersonalAccessToken || ! self::isScopedExtranetToken($token)) {
            return null;
        }

        $abilities = $token->abilities ?? [];
        if (! in_array(self::ABILITY_SERVICES, $abilities, true)) {
            return [];
        }

        $cityIds = [];
        foreach ($abilities as $ability) {
            if (! is_string($ability) || ! str_starts_with($ability, self::ABILITY_CITY_PREFIX)) {
                continue;
            }

            $rawId = substr($ability, strlen(self::ABILITY_CITY_PREFIX));
            if ($rawId !== '' && ctype_digit($rawId)) {
                $cityIds[] = (int) $rawId;
            }
        }

        return array_values(array_unique($cityIds));
    }

    /**
     * Return the IDs of churches directly writable by the user for permanent token issuance.
     *
     * @param  User  $user  Authenticated user.
     * @return Collection<int, int> Directly writable church IDs (all churches for Super-Admin).
     */
    public static function directlyWritableCityIds(User $user): Collection
    {
        if ($user->hasRole(RoleService::ROLE_SUPER_ADMIN)) {
            return self::toIntIdCollection(City::query()->pluck('id'));
        }

        return self::toIntIdCollection($user->writableCities()->pluck('cities.id'));
    }

    /**
     * Compute the effective writable church IDs for the current Extranet request.
     *
     * @param  User  $user  Authenticated user.
     * @param  object|null  $token  Current Sanctum access token, if any.
     * @return Collection<int, int> Church IDs the current request may manage.
     */
    public static function effectiveWritableCityIds(User $user, ?object $token): Collection
    {
        $tokenCityIds = self::allowedCityIds($token);
        if ($tokenCityIds !== null) {
            $directIds = self::directlyWritableCityIds($user);

            /** @var array<int, int> $filtered */
            $filtered = [];
            foreach ($directIds as $id) {
                if (in_array($id, $tokenCityIds, true)) {
                    $filtered[] = $id;
                }
            }

            return new Collection($filtered);
        }

        if ($user->hasRole(RoleService::ROLE_SUPER_ADMIN)) {
            return self::toIntIdCollection(City::query()->pluck('id'));
        }

        return self::toIntIdCollection($user->writableCities->pluck('id'));
    }

    /**
     * Compute the effective readable church IDs for the current Extranet request.
     *
     * @param  User  $user  Authenticated user.
     * @param  object|null  $token  Current Sanctum access token, if any.
     * @return Collection<int, int> Church IDs the current request may read.
     */
    public static function effectiveVisibleCityIds(User $user, ?object $token): Collection
    {
        $tokenCityIds = self::allowedCityIds($token);
        if ($tokenCityIds !== null) {
            return self::effectiveWritableCityIds($user, $token);
        }

        if ($user->hasRole(RoleService::ROLE_SUPER_ADMIN)) {
            return self::toIntIdCollection(City::query()->pluck('id'));
        }

        return self::toIntIdCollection(
            $user->visibleCities()
                ->pluck('cities.id')
                ->merge($user->writableCities->pluck('id'))
                ->unique()
        );
    }

    /**
     * Check whether the current request may create a service or event in the given church.
     *
     * @param  User  $user  Authenticated user.
     * @param  object|null  $token  Current Sanctum access token, if any.
     * @param  int  $cityId  Target church ID.
     * @return bool True when creation in the target church is permitted.
     */
    public static function canCreateInCity(User $user, ?object $token, int $cityId): bool
    {
        if (! self::effectiveWritableCityIds($user, $token)->contains($cityId)) {
            return false;
        }

        if (self::isScopedExtranetToken($token)) {
            return $user->can(RoleService::PERMISSION_CREATE_CHURCH_API_TOKENS);
        }

        return $user->can('create', Service::class);
    }

    /**
     * Check whether the current request may mutate an existing service or event.
     *
     * @param  User  $user  Authenticated user.
     * @param  object|null  $token  Current Sanctum access token, if any.
     * @param  Service  $service  Target service or event record.
     * @param  string  $ability  Policy ability to check for non-scoped credentials.
     * @return bool True when the mutation is permitted.
     */
    public static function canManageService(
        User $user,
        ?object $token,
        Service $service,
        string $ability = 'update'
    ): bool {
        if ($service->isTemplate() || $service->city_id === null) {
            return false;
        }

        if (! self::effectiveWritableCityIds($user, $token)->contains((int) $service->city_id)) {
            return false;
        }

        if (self::isScopedExtranetToken($token)) {
            return $user->can(RoleService::PERMISSION_CREATE_CHURCH_API_TOKENS);
        }

        return $user->can($ability, $service);
    }

    /**
     * Format a personal access token for the token-management UI with scope metadata.
     *
     * @param  PersonalAccessToken  $token  Persisted Sanctum token.
     * @param  array<int, string>  $cityNamesById  Map of church IDs to display names.
     * @return array<string, mixed> Serializable token metadata for the Inertia view.
     */
    public static function describeToken(PersonalAccessToken $token, array $cityNamesById): array
    {
        $scopeType = 'personal';
        $cityId = null;
        $cityName = null;
        $scopeLabel = 'Persönlich';

        if (self::isCalendarFeedToken($token)) {
            $scopeType = 'calendar_feed';
            $scopeLabel = 'Kalender-Feed';
        } elseif (self::isScopedExtranetToken($token)) {
            $scopeType = 'church_extranet';
            $cityIds = self::allowedCityIds($token) ?? [];
            $cityId = $cityIds[0] ?? null;
            $cityName = $cityId !== null ? ($cityNamesById[$cityId] ?? null) : null;
            $scopeLabel = $cityName !== null
                ? 'Kirchengemeinde: '.$cityName
                : ($cityId !== null ? 'Kirchengemeinde #'.$cityId : 'Extranet-Service-API');
        }

        return [
            'id' => $token->id,
            'name' => $token->name,
            'created_at' => $token->created_at?->toISOString(),
            'last_used_at' => $token->last_used_at?->toISOString(),
            'scope_type' => $scopeType,
            'city_id' => $cityId,
            'city_name' => $cityName,
            'scope_label' => $scopeLabel,
        ];
    }

    /**
     * Normalize raw plucked identifier values into an integer collection.
     *
     * @param  iterable<mixed>  $values  Raw identifier values from database pluck queries.
     * @return Collection<int, int> Normalized list of integer church IDs.
     */
    private static function toIntIdCollection(iterable $values): Collection
    {
        /** @var array<int, int> $ids */
        $ids = [];
        foreach ($values as $value) {
            if (is_int($value) || (is_string($value) && is_numeric($value))) {
                $ids[] = (int) $value;
            }
        }

        return new Collection($ids);
    }
}
