<?php

namespace App\Http\Controllers;

use App\Models\Ads\AdChannel;
use App\Models\Places\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AdChannelController extends AbstractCRUDController
{
    protected string $modelClass = AdChannel::class;

    protected function getResourcesForEditor(Request $request, $model = null): array
    {
        $data = [];

        if ($model) {
            $data['city'] = $model->city;
        } elseif ($request->has('city')) {
            $data['city'] = City::findOrFail($request->get('city'));
        }
        return $data;
    }


}
