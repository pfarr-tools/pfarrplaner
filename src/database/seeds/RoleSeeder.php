<?php

namespace Database\Seeders;

use App\Services\RoleConfigurationSynchronizer;
use Illuminate\Database\Seeder;

/**
 * Seeds the configured roles and permissions.
 */
class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(RoleConfigurationSynchronizer $synchronizer): void
    {
        $synchronizer->synchronize();
    }
}
