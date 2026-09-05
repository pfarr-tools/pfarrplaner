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
 */

namespace App\Listeners;

use Illuminate\Support\Facades\Artisan;
use Spatie\Backup\Events\BackupManifestWasCreated;

class PrepareBackup
{
    /**
     * Handle the event.
     *
     * @param BackupManifestWasCreated $event
     * @return void
     */
    public function handle(BackupManifestWasCreated $event): void
    {
        Artisan::call('telescope:prune', ['--hours' => 24]);
    }
}
