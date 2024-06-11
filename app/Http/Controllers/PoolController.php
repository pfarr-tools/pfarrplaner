<?php

namespace App\Http\Controllers;

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
        $people =  ListedPerson::select(['id', 'name'])
            ->visibleFor(Auth::user())
            ->get();
        return compact('cities', 'people');
    }


}
