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

use App\Events\Models\Baptism\CreatedBaptism;
use App\Events\Models\Baptism\DeletedBaptism;
use App\Events\Models\Baptism\UpdatedBaptism;
use App\Models\AbstractModel;
use App\Models\People\User;
use App\Models\Rites\Baptism;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BaptismUnitTest extends TestCase
{
    public function testBaptismHasServiceRelationship(): void
    {
        $baptism = Baptism::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $baptism->service());
    }

    public function testBaptismCanBeCreatedViaFactory(): void
    {
        $baptism = Baptism::factory()->create();
        $this->assertCount(1, Baptism::all());
        $this->assertNotNull($baptism->service_id);
    }

    public function testBaptismExtendsAbstractModel(): void
    {
        $this->assertTrue(is_subclass_of(Baptism::class, AbstractModel::class));
    }

    public function testBaptismControllerClassExists(): void
    {
        $this->assertTrue(is_subclass_of(Baptism::controllerClass(), \App\Http\Controllers\AbstractCRUDController::class));
    }

    public function testBaptismContractsResolve(): void
    {
        $this->assertInstanceOf(\App\Actions\Baptism\CreateBaptism::class, app(Baptism::getContractName('create')));
        $this->assertInstanceOf(\App\Actions\Baptism\UpdateBaptism::class, app(Baptism::getContractName('update')));
        $this->assertInstanceOf(\App\Actions\Baptism\DeleteBaptism::class, app(Baptism::getContractName('delete')));
    }

    public function testBaptismCanBeCreatedViaAction(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $baptism = app(Baptism::getContractName('create'))->create($user, []);

        $this->assertInstanceOf(Baptism::class, $baptism);
        Event::assertDispatched(CreatedBaptism::class);
    }

    public function testBaptismCanBeUpdatedViaAction(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $baptism = Baptism::factory()->create();

        $updated = app(Baptism::getContractName('update'))->update($user, $baptism, [
            'candidate_name' => 'Neuer Name',
            'city_id' => $baptism->city_id,
            'service_id' => $baptism->service_id,
        ]);

        $this->assertSame('Neuer Name', $updated->candidate_name);
        Event::assertDispatched(UpdatedBaptism::class);
    }

    public function testBaptismCanBeDeletedViaAction(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $baptism = Baptism::factory()->create();

        $result = app(Baptism::getContractName('delete'))->delete($user, $baptism);

        $this->assertTrue($result);
        $this->assertCount(0, Baptism::all());
        Event::assertDispatched(DeletedBaptism::class);
    }
}
