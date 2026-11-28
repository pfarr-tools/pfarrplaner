<?php

namespace App\Http\Controllers;

use App\Models\Leave\Absence;
use App\Models\Leave\Pool;
use App\Models\People\ListedPerson;
use App\Models\People\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles CRUD requests for Pool records.
 *
 * @extends AbstractCRUDController<Pool>
 */
class PoolController extends AbstractCRUDController
{
    protected string $modelClass = Pool::class;

    /** @var class-string<Pool> */
    protected string $model = Pool::class;

    /**
     * @param  Request  $request  Current request.
     * @param  Pool|null  $model  Model being edited.
     * @return array<string, mixed>
     */
    protected function getResourcesForEditor(Request $request, $model = null): array
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        $cities = $user->cities;
        $people = ListedPerson::select(['id', 'first_name', 'last_name', 'title'])
            ->visibleFor($user)
            ->get();

        return compact('cities', 'people');
    }

    /**
     * Render the public view for a pool.
     */
    public function public(Pool $pool): View
    {
        $pool->load('users', 'cities', 'poolmasters');

        $startOfRelevantPeriod = now()->startOfDay();
        $endOfRelevantPeriod = now()->addMonth()->endOfDay();

        $absences = [];
        foreach ($pool->cities as $city) {
            foreach ($city->pastors as $user) {
                if (! isset($absences[$user->id])) {
                    $absences[$user->id] = Absence::where('user_id', $user->id)
                        ->where('from', '<=', $endOfRelevantPeriod)
                        ->where('to', '>=', $startOfRelevantPeriod)
                        ->orderBy('from')
                        ->get()
                        ->map(function ($absence) {
                            return $absence->fullReplacementArray;
                        });
                }
            }
        }

        return view(
            'public.pool',
            compact(
                'startOfRelevantPeriod',
                'endOfRelevantPeriod',
                'pool',
                'absences'
            )
        );
    }
}
