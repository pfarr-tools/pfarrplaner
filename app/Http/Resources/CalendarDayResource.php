<?php

namespace App\Http\Resources;

use App\Models\Leave\Absence;
use App\Models\Service;
use App\Services\LiturgyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CalendarDayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $date = Carbon::parse($this->resource);
        $liturgy = LiturgyService::getLiturgyInfoByDate($date);

        // use raw query to avoid any relationships
        $services = DB::select('SELECT id, slug, date, city_id FROM services WHERE date(`date`) = ? ORDER BY date', [$this->resource]);

        $absences = Absence::setEagerLoads([])->with('user', function ($query) {
            $query->setEagerLoads([])->with([]);
        })->byPeriod($date, $date->copy()->endOfDay())
            ->visibleForUser(Auth::user())
            ->showInCalendar()
            ->get();

        return [
            'date' => $this->resource,
            'liturgy' => isset($liturgy[0]) ? [
                'title' => $liturgy[0]['title'],
                'litColor' => $liturgy[0]['litColor'],
            ] : [],
            'absences' => CalendarAbsenceResource::collection($absences),
            'services' => new CalendarServiceIdCollectionResource($services),
        ];
    }
}
