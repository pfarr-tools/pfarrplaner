<?php

namespace App\Http\Controllers;

use App\Models\Ads\AdChannel;
use Illuminate\Http\Request;

class AdChannelController extends AbstractCRUDController
{
    protected string $modelClass = AdChannel::class;

    protected function preFillNewModel(Request $request): array
    {
        $data = parent::preFillNewModel($request);
        $data['city_id'] = $request->get('city') ?? null;

        return $data;
    }
}
