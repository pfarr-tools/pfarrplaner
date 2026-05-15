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

use App\Models\Parish;
use App\Models\Places\City;
use App\Services\RoleService;
use Tests\AbstractModelFeatureTest;

class ParishFeatureTest extends AbstractModelFeatureTest
{
    protected $modelClass = Parish::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $this->testUser->assignRole(RoleService::ROLE_ADMIN);
    }

    protected function factory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        $city = City::factory()->create();

        return Parish::factory()->state([
            'city_id' => $city->id,
        ]);
    }

    protected function getActionRedirectUrl($model = null): string
    {
        return route('admin.city.edit', ['modelId' => $model->city_id, 'tab' => 'parishes']);
    }
}
