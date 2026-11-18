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

$applicationUrl = rtrim((string) config('app.url'), '/');
$configuredOrigins = (string) env('PASSKEYS_ALLOWED_ORIGINS', '');
$configuredOrigins = $configuredOrigins !== '' ? $configuredOrigins : $applicationUrl;

return [
    'relying_party_id' => env('PASSKEYS_RELYING_PARTY_ID')
        ?: parse_url($applicationUrl, PHP_URL_HOST),
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', $configuredOrigins)))),
    'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET') ?: config('app.key'),
    'timeout' => 60000,
    'guard' => 'web',
    'middleware' => ['web'],
    'management_middleware' => ['auth'],
    'throttle' => 'throttle:6,1',
    'redirect' => '/',
];
