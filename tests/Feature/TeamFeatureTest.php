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
 */

namespace Tests\Feature;

use App\Models\People\Team;
use App\Models\Places\City;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\AbstractModelFeatureTest;

class TeamFeatureTest extends AbstractModelFeatureTest
{
    protected $modelClass = Team::class;
    private City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->city = City::factory()->create();
        $this->testUser->cities()->attach($this->city->id, ['permission' => 'w']);
    }

    protected function factory(): Factory
    {
        return Team::factory()->state(['city_id' => $this->city->id]);
    }
}
