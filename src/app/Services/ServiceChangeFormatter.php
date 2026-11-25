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

use App\Models\Places\City;
use App\Models\Service;
use DateTimeInterface;

/**
 * Formats structural service diffs for the service update email.
 */
class ServiceChangeFormatter
{
    /**
     * Converts changed model paths into labelled, readable email rows.
     *
     * @param  Service  $service  Changed service model.
     * @param  array<string, array{original: mixed, changed: mixed}>  $changes  Flat field diff.
     * @return array<int, array{label: string, original: string, changed: string}> Formatted rows.
     */
    public static function format(Service $service, array $changes): array
    {
        ksort($changes, SORT_STRING);

        $rows = [];
        foreach ($changes as $path => $change) {
            if ($path === 'participants' || ! is_array($change)
                || ! array_key_exists('original', $change) || ! array_key_exists('changed', $change)) {
                continue;
            }

            $rows[] = [
                'label' => $service->getAttributeLabel($path),
                'original' => self::formatAttributeValue($service, $path, $change['original']),
                'changed' => self::formatAttributeValue($service, $path, $change['changed']),
            ];
        }

        return $rows;
    }

    /**
     * Resolves selected foreign keys to names before formatting them for email.
     *
     * @param  Service  $service  Changed service model.
     * @param  string  $path  Changed attribute path.
     * @param  mixed  $value  Change value.
     * @return string Readable value.
     */
    private static function formatAttributeValue(Service $service, string $path, mixed $value): string
    {
        if ($path === 'city_id' && $value !== null) {
            $city = City::query()->find((int) $value);

            return (string) ($city ? $city->name : $value);
        }

        return self::formatValue($value);
    }

    /**
     * Converts nested values into readable text for a mail table cell.
     *
     * @param  mixed  $value  Change value.
     * @return string Readable value.
     */
    private static function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Ja' : 'Nein';
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format('d.m.Y H:i');
        }
        if (is_array($value)) {
            if ($value === []) {
                return '—';
            }
            if (array_is_list($value)) {
                return implode(', ', array_map(self::formatValue(...), $value));
            }

            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }
}
