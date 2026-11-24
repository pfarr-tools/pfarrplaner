<?php

namespace App\HomeScreen\Tabs;

use App\Http\Resources\HomeScreen\PredicantServicesTabResource;
use App\Models\People\User;
use App\Models\Service;
use App\Services\RoleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Displays future services that still need a predicant.
 */
class PredicantServicesHomeScreenTab extends AbstractHomeScreenTab
{
    protected $title = 'Offene Gottesdienste';

    protected $description = 'Zeigt Gottesdienste mit Bedarf an Prädikant:innen';

    protected $query = null;

    /**
     * Initialize the tab configuration and its service query.
     */
    public function __construct($config = [])
    {
        $this->setDefaultConfig($config, ['cities' => []]);
        parent::__construct($config);
        $this->query = $this->buildQuery();
    }

    /**
     * Check whether the open services tab is available to the user.
     *
     * @param  User|null  $user  User whose ministry roles should be checked
     * @return bool Whether the user can view open services
     */
    public function isAvailable(?User $user = null): bool
    {
        $user ??= Auth::user();

        return $user && $user->hasAnyRole([
            RoleService::ROLE_PREDICANT,
            'Gottesdienstbearbeiter:in',
            RoleService::ROLE_PASTOR,
        ]);
    }

    /**
     * Count future services that need a predicant.
     */
    public function getCount(): int
    {
        return $this->query->count();
    }

    /**
     * Add matching services to the tab view data.
     */
    public function getContent($data = [])
    {
        $data['services'] = $this->query->get();

        return parent::getContent($data);
    }

    /**
     * Serialize matching services and visible cities for the frontend.
     */
    public function toArray($data = [])
    {
        $data['services'] = $this->query->get();
        $data['count'] = count($data['services']);
        $data['cities'] = Auth::user()->visibleCities()->get();

        return new PredicantServicesTabResource(parent::toArray($data));
    }

    /**
     * Build the query for future services needing a predicant in visible cities.
     */
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
