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

namespace Tests\Unit\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Verifies production schema repair for missing failed-job storage.
 */
class RepairMissingFailedJobsTableUnitTest extends TestCase
{
    /**
     * Recreate the failed-jobs table when its historical migration is already recorded.
     */
    public function test_it_recreates_a_missing_failed_jobs_table(): void
    {
        Schema::dropIfExists('failed_jobs');

        $migration = require database_path('migrations/2026_11_20_110000_repair_missing_failed_jobs_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('failed_jobs'));
        $this->assertSame(
            ['id', 'connection', 'queue', 'payload', 'exception', 'failed_at'],
            Schema::getColumnListing('failed_jobs')
        );
    }
}
