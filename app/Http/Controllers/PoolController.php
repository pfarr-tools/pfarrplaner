<?php

namespace App\Http\Controllers;

use App\Models\Leave\Absence;
use App\Models\Leave\Pool;
use App\Models\People\ListedPerson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PoolController extends AbstractCRUDController
{

    protected string $modelClass = Pool::class;
    protected $model = Pool::class;

    protected function getResourcesForEditor(Request $request, $model = null): array
    {
        $cities = Auth::user()->cities;
        $people = ListedPerson::select(['id', 'name'])
            ->visibleFor(Auth::user())
            ->get();
        return compact('cities', 'people');
    }

    public function public(Pool $pool)
    {
        $pool->load('users', 'cities', 'poolmasters');

        $startOfRelevantPeriod = now()->startOfDay();
        $endOfRelevantPeriod = now()->addMonth(1)->endOfDay();

        $absences = [];
        foreach ($pool->cities as $city) {
            foreach ($city->pastors as $user) {
                if (!isset($absences[$user->id])) {
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
