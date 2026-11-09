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

namespace App\Console\Commands;

use App\Services\RoleConfigurationSynchronizer;
use Illuminate\Console\Command;

/**
 * Synchronizes the configured roles and permissions with the application database.
 */
class SyncRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:sync {--dry-run : Show changes without writing to the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize configured roles and permissions';

    /**
     * @param  RoleConfigurationSynchronizer  $synchronizer  Role synchronization service.
     * @return int Command exit status.
     */
    public function handle(RoleConfigurationSynchronizer $synchronizer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = $synchronizer->synchronize($dryRun);
        $prefix = $dryRun ? 'Vorschau: ' : '';

        $this->info($prefix.'Rollen und Berechtigungen abgeglichen.');
        $this->line("Angelegt: {$result->created}, aktualisiert: {$result->updated}, unverändert: {$result->unchanged}.");
        foreach ($result->changes as $change) {
            $this->line("- {$change}");
        }

        return self::SUCCESS;
    }
}
