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

namespace App\Console\Commands\Trash;

use App\Http\Controllers\PapierkorbController;
use App\Models\Leave\Absence;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class PruneTrash extends Command
{
    /**
     * @var string
     */
    protected $signature = 'trash:prune {--days=30}';

    /**
     * @var string
     */
    protected $description = 'Permanently delete trash entries older than the retention period';

    /**
     * @return int
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days);
        $pruned = 0;

        foreach ($this->supportedModels() as $label => $modelClass) {
            $records = $modelClass::onlyTrashed()
                ->where('deleted_at', '<', $cutoff)
                ->get();

            /** @var Model $record */
            foreach ($records as $record) {
                $record->forceDelete();
                $pruned++;
            }

            if ($records->count() > 0) {
                $this->line($label . ': ' . $records->count() . ' Einträge endgültig gelöscht.');
            }
        }

        $this->info('Papierkorb bereinigt, insgesamt ' . $pruned . ' Einträge gelöscht.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, class-string<Model>>
     */
    protected function supportedModels(): array
    {
        return [
            'Gottesdienste' => Service::class,
            'Abwesenheiten' => Absence::class,
            'Taufen' => Baptism::class,
            'Bestattungen' => Funeral::class,
            'Trauungen' => Wedding::class,
        ];
    }
}
