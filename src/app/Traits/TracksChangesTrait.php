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

namespace App\Traits;

use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Trait TracksChangesTrait
 */
trait TracksChangesTrait
{
    /**
     * Snapshot of the model state recorded before changes were made.
     *
     * @var array<string, mixed> Normalized values keyed by attribute path.
     */
    public $dataBeforeChanges = [];

    /**
     * Collected differences between the recorded snapshot and the saved state.
     *
     * @var array<string, array{original: mixed, changed: mixed}> Old and new value keyed by attribute path.
     */
    public $changed = [];

    /**
     * Start tracking changed
     *
     * @return void
     */
    public function trackChanges()
    {
        if (isset($this->forceTracking)) {
            /** @var array<int|string, list<string>|string>|string $forceTracking */
            $forceTracking = $this->forceTracking;
            $this->load($forceTracking);
        }
        $this->dataBeforeChanges = $this->createSnapshot();
    }

    /**
     * Creates a normalized snapshot of tracked model attributes.
     *
     * @return array<string, mixed> Snapshot indexed by attribute path.
     */
    public function createSnapshot(): array
    {
        $snapshot = [];
        $excludedAttributes = ['id', 'created_at', 'updated_at', 'deleted_at'];
        if (is_array($this->attributesToSkipTracking)) {
            $excludedAttributes = array_merge($excludedAttributes, $this->attributesToSkipTracking);
        }

        foreach ($this->getAttributes() as $attribute => $value) {
            if (in_array($attribute, $excludedAttributes, true)) {
                continue;
            }
            $snapshot[$attribute] = $this->normalizeSnapshotValue($this->getAttributeValue($attribute));
        }

        if (is_array($this->appendsToTracking)) {
            foreach ($this->appendsToTracking as $attribute) {
                $snapshot[$attribute] = $this->normalizeSnapshotValue($this->$attribute);
            }
        }

        if (is_array($this->relationsToTrack)) {
            foreach ($this->relationsToTrack as $relation) {
                $this->unsetRelation($relation);
                $snapshot[$relation] = $this->normalizeSnapshotValue($this->$relation);
            }
        }

        /** @var array<string, mixed> $normalized */
        $normalized = $this->normalizeSnapshotValue($snapshot);

        return $normalized;
    }

    /**
     * Normalizes values so snapshots compare independently of key and relation order.
     *
     * @param  mixed  $value  Snapshot value.
     * @return mixed Normalized value.
     */
    protected function normalizeSnapshotValue(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d\TH:i:s.uP');
        }
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        } elseif ($value instanceof JsonSerializable) {
            $value = $value->jsonSerialize();
        }

        if (is_array($value)) {
            foreach ($value as $key => $nestedValue) {
                if (in_array($key, ['created_at', 'updated_at'], true)) {
                    unset($value[$key]);

                    continue;
                }
                $value[$key] = $this->normalizeSnapshotValue($nestedValue);
            }

            if (array_is_list($value) && $value !== [] && $this->containsIdentifiableRecords($value)) {
                /** @var list<array{id: mixed}> $value */
                usort($value, fn (array $left, array $right): int => $left['id'] <=> $right['id']);
            } else {
                ksort($value, SORT_STRING);
            }
        }

        return $value;
    }

    /**
     * Determines whether a list contains records that can be sorted by their stable IDs.
     *
     * @param  array<int, mixed>  $values  List to inspect.
     * @return bool Whether all values have an ID.
     */
    protected function containsIdentifiableRecords(array $values): bool
    {
        foreach ($values as $value) {
            if (! is_array($value) || ! array_key_exists('id', $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isChanged()
    {
        $snapshot = $this->createSnapshot();
        $original = $this->dataBeforeChanges;

        return $snapshot !== $original;
    }

    /**
     * Stores the current diff after a model has been saved.
     *
     * @return void
     */
    public function storeDiff()
    {
        $this->changed = $this->diff();
    }

    /**
     * Return all changed attributes and relations as stable field paths.
     *
     * @return array<string, array{original: mixed, changed: mixed}> Old and new value keyed by attribute path.
     */
    public function diff(): array
    {
        $this->refresh();

        $diff = [];
        $this->collectChanges($this->dataBeforeChanges, $this->createSnapshot(), '', $diff);

        return $diff;
    }

    /**
     * Recursively records changed scalar values under stable dot-separated paths.
     *
     * @param  mixed  $original  Value before the update.
     * @param  mixed  $changed  Value after the update.
     * @param  string  $path  Current field path.
     * @param  array<string, array{original: mixed, changed: mixed}>  $diff  Collected changes.
     */
    protected function collectChanges(mixed $original, mixed $changed, string $path, array &$diff): void
    {
        if ($original === $changed) {
            return;
        }

        if ($this->isAssociativeArray($original) || $this->isAssociativeArray($changed)) {
            $originalValues = is_array($original) ? $original : [];
            $changedValues = is_array($changed) ? $changed : [];
            $keys = array_unique(array_merge(array_keys($originalValues), array_keys($changedValues)));
            sort($keys, SORT_STRING);

            foreach ($keys as $key) {
                $childPath = $path === '' ? (string) $key : $path.'.'.$key;
                $this->collectChanges(
                    $originalValues[$key] ?? null,
                    $changedValues[$key] ?? null,
                    $childPath,
                    $diff,
                );
            }

            return;
        }

        $diff[$path] = ['original' => $original, 'changed' => $changed];
    }

    /**
     * Checks if a non-list array should be traversed as a nested field map.
     *
     * @param  mixed  $value  Value to inspect.
     * @return bool Whether the value is an associative array.
     */
    protected function isAssociativeArray(mixed $value): bool
    {
        return is_array($value) && $value !== [] && ! array_is_list($value);
    }
}
