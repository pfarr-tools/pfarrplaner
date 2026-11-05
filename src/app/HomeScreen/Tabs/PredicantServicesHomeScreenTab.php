<?php

namespace App\HomeScreen\Tabs;

use App\Http\Resources\HomeScreen\PredicantServicesTabResource;
use App\Models\Service;
use App\Services\RoleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PredicantServicesHomeScreenTab extends AbstractHomeScreenTab
{
    protected $title = 'Offene Gottesdienste';

    protected $description = 'Zeigt Gottesdienste mit Bedarf an Prädikant:innen';

    protected $query = null;

    public function __construct($config = [])
    {
        $this->setDefaultConfig($config, ['cities' => []]);
        parent::__construct($config);
        $this->query = $this->buildQuery();
    }

    public function isAvailable(): bool
    {
        return Auth::check() && Auth::user()->hasAnyRole([
            RoleService::ROLE_PREDICANT,
            'Gottesdienstbearbeiter:in',
            RoleService::ROLE_PASTOR,
        ]);
    }

    public function getCount(): int
    {
        return $this->query->count();
    }

    public function getContent($data = [])
    {
        $data['services'] = $this->query->get();

        return parent::getContent($data);
    }

    public function toArray($data = [])
    {
        $data['services'] = $this->query->get();
        $data['count'] = count($data['services']);
        $data['cities'] = Auth::user()->visibleCities()->get();

        return new PredicantServicesTabResource(parent::toArray($data));
    }

    public function buildQuery()
    {
        $visibleCityIds = Auth::user()->visibleCities()->pluck('cities.id');
        $query = Service::with(['baptisms', 'weddings', 'funerals', 'location', 'city'])
            ->startingFrom(Carbon::now())
            ->where('need_predicant', true)
            ->whereDoesntHave('pastors')
            ->inCities($visibleCityIds)
            ->ordered();

        return $query;
    }
}
