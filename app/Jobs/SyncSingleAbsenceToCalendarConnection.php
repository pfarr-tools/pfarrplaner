<?php

namespace App\Jobs;

use App\Absence;
use App\CalendarConnection;
use App\Calendars\SyncEngines\SyncEngines;
use App\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSingleAbsenceToCalendarConnection implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var Service */
    protected $absence;

    /** @var CalendarConnection */
    protected $calendarConnection;

    /**
     * Create a new job instance
     * @param CalendarConnection $calendarConnection
     * @param Absence $absence
     */
    public function __construct(CalendarConnection $calendarConnection, Absence $absence)
    {
        $this->calendarConnection = $calendarConnection;
        $this->absence = $absence;
    }

    /**
     * Execute the job.
     * Gets the appropriate SyncEngine and syncs the service
     *
     * @return void
     */
    public function handle()
    {
        // temporarily disabled
        return;
        Log::debug('Executing sync job for absence #'.$this->absence->id.' on CalendarConnection #'.$this->calendarConnection->id);
        $syncEngine = $this->calendarConnection->getSyncEngine();
        if ($syncEngine) {
            Log::debug('Sync engine found: '.get_class($syncEngine));
            $syncEngine->syncSingleAbsence($this->absence);
        } else {
            Log::error('No sync engine found for CalendarConnection #'.$this->calendarConnection->id);
        }
    }
}
