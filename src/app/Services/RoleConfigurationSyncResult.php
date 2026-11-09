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

/**
 * Contains the result counters of a role configuration synchronization.
 */
class RoleConfigurationSyncResult
{
    /**
     * @param  int  $created  Number of records created.
     * @param  int  $updated  Number of records updated.
     * @param  int  $unchanged  Number of records already matching the configuration.
     * @param  int  $skipped  Number of records intentionally left untouched.
     * @param  array<int, string>  $changes  Human-readable changed records.
     */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $unchanged = 0,
        public int $skipped = 0,
        public array $changes = [],
    ) {}
}
