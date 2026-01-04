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
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers;

use App\Integrations\KonfiApp\KonfiAppIntegration;
use App\Integrations\Youtube\YoutubeIntegration;
use App\Models\AbstractModel;
use App\Services\MinistryService;
use App\Models\Places\City;
use App\Models\Service;
use App\Traits\HandlesAttachedImageTrait;
use App\Traits\HandlesAttachmentsTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Controller for handling city-related functionality.
 *
 * Handles CRUD operations and additional functionality such as
 * retrieving models, managing attachments, and generating QR codes
 * for city-related events or services.
 */
class CityController extends AbstractCRUDController
{

    use HandlesAttachmentsTrait;
    use HandlesAttachedImageTrait;

    protected string $modelClass = City::class;
    protected $model = City::class;

    /**
     * Class constructor.
     *
     * Applies the 'auth' middleware to all routes, except for the 'qr' route.
     */
    public function __construct()
    {
        $this->middleware('auth')->except('qr');
    }


    /**
     * @inheritDoc
     */
    protected function getModelsForIndex(): Collection {
        $user = Auth::user();
        if ($user->isAdmin) {
            return ($this->modelClass)::all();
        } else {
            return $user->cities()->get();
        }
    }


    /**
     * @inheritDoc
     */
    protected function getSingleModel(Request $request, $modelId = null, $relations = []): AbstractModel
    {
        return parent::getSingleModel($request, $modelId, $relations)->load(['adChannels', 'locations', 'parishes']);
    }

    protected function getResourcesForEditor(Request $request, $model = null): array
    {
        $data = parent::getResourcesForEditor($request, $model);
        $data['canDelete'] = Auth::user()->can('delete', $model);
        $data['possibleChildren'] = City::where('is_org', '!=', 1)
            ->whereNull('parent_id')
            ->get()
            ->merge($model ? $model->children : collect())
            ->unique()
            ->sortBy('name');
        $data['streams'] = [];
        $data['activeTab'] = 'home';
        return $data;
    }


    /**
     * Show QR codes
     * @param Request $request
     * @param string $city
     * @return \Inertia\Response
     */
    public function qr(Request $request, $city) {
        $city = City::where('name', 'like', '%' . str_replace('-', ' ', $city) . '%')->first();
        $services = Service::where('city_id', $city->id)->whereDate('date', Carbon::now()->setTime(0,0,0))
            ->whereNotNull('konfiapp_event_qr')->get();
        $types = KonfiAppIntegration::get($city)->listEventTypes();

        $servicesWithoutQR = Service::where('city_id', $city->id)->whereDate('date', Carbon::now()->setTime(0,0,0))
            ->whereNull('konfiapp_event_qr')->get();

        return Inertia::render('Public/City/QR', compact('services', 'city', 'types', 'servicesWithoutQR'));
    }

}
