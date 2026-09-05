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

namespace Tests\Unit;

use App\Models\People\User;
use App\Models\Places\City;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\AbstractSimpleModelUnitTest;

class UserUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = User::class;
    protected bool $hasPolicy = true;
    protected bool $hasFactory = true;

    public function testUserHasAdminCitiesRelationship(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(BelongsToMany::class, $user->adminCities());
    }

    public function testUserHasWritableCitiesRelationship(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(BelongsToMany::class, $user->writableCities());
    }

    public function testUserFullNameIncludesFirstAndLastName(): void
    {
        $user = User::factory()->create(['first_name' => 'Maria', 'last_name' => 'Muster']);
        $this->assertStringContainsString('Maria', $user->name);
        $this->assertStringContainsString('Muster', $user->name);
    }

    public function testUserCanBeCreatedViaFactory(): void
    {
        $user = User::factory()->create();
        $this->assertCount(1, User::all());
        $this->assertNotEmpty($user->last_name);
    }

    protected function setUp(): void
    {
        parent::setUp();
    }
}
