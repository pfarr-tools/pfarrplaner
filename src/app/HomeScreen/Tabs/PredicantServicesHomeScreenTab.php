<?php

namespace App\HomeScreen\Tabs;

use App\Http\Resources\HomeScreen\PredicantServicesTabResource;
use App\Models\People\User;
use App\Models\Service;
use App\Services\RoleService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/** Home screen tab for services by predicant. */
class PredicantServicesHomeScreenTab extends AbstractHomeScreenTab
{
    protected $title = 'Offene Gottesdienste';

    protected $description = 'Zeigt Gottesdienste mit Bedarf an Prädikant:innen';

    /**
     * @var Builder<Service>
     */
    protected $query = null;

    /**
     * @param  array<string, mixed>  $config  Tab configuration.\n     * @return void
     */
    public function __construct($config = [])
    {
        $this->setDefaultConfig($config, ['cities' => []]);
        parent::__construct($config);
        $this->query = $this->buildQuery();
    }

    public function isAvailable(?User $user = null): bool
    {
        $user ??= Auth::user();
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole([
            RoleService::ROLE_PREDICANT,
            'Gottesdienstbearbeiter:in',
            RoleService::ROLE_PASTOR,
        ]);
    }

    public function getCount(): int
    {
        return $this->query->count();
    }

    /**
     * @param  array<string, mixed>  $data  View data.\n     * @return mixed
     */
    public function getContent($data = [])
    {
        $data['services'] = $this->query->get();

        return parent::getContent($data);
    }

    /**
     * @param  array<string, mixed>  $data  Additional data.\n     * @return array<string, mixed>
     */
    public function toArray($data = [])
    {
        $data['services'] = $this->query->get();
        $data['count'] = count($data['services']);
        $data['cities'] = $this->user()->visibleCities()->get();

        return new PredicantServicesTabResource(parent::toArray($data));
    }

    /**
     * Build the query
     *
     * @return Builder<Service>
     */
    public function buildQuery()
    {
        $visibleCityIds = $this->user()->visibleCities()->pluck('cities.id');
        $query = Service::with(['baptisms', 'weddings', 'funerals', 'location', 'city'])
            ->startingFrom(Carbon::now())
            ->where('need_predicant', true)
            ->whereDoesntHave('pastors')
            ->inCities($visibleCityIds)
            ->ordered();

        return $query;
    }
}
