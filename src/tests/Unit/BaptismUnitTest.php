<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 */

namespace Tests\Unit;

use App\Actions\Baptism\CreateBaptism;
use App\Actions\Baptism\DeleteBaptism;
use App\Actions\Baptism\UpdateBaptism;
use App\Events\Models\Baptism\CreatedBaptism;
use App\Events\Models\Baptism\DeletedBaptism;
use App\Events\Models\Baptism\UpdatedBaptism;
use App\Http\Controllers\AbstractCRUDController;
use App\Models\AbstractModel;
use App\Models\People\User;
use App\Models\Rites\Baptism;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BaptismUnitTest extends TestCase
{
    public function test_baptism_has_service_relationship(): void
    {
        $baptism = Baptism::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $baptism->service());
    }

    public function test_baptism_can_be_created_via_factory(): void
    {
        $baptism = Baptism::factory()->create();
        $this->assertCount(1, Baptism::all());
        $this->assertNotNull($baptism->service_id);
    }

    public function test_baptism_extends_abstract_model(): void
    {
        $this->assertTrue(is_subclass_of(Baptism::class, AbstractModel::class));
    }

    public function test_baptism_controller_class_exists(): void
    {
        $this->assertTrue(is_subclass_of(Baptism::controllerClass(), AbstractCRUDController::class));
    }

    public function test_baptism_contracts_resolve(): void
    {
        $this->assertInstanceOf(CreateBaptism::class, app(Baptism::getContractName('create')));
        $this->assertInstanceOf(UpdateBaptism::class, app(Baptism::getContractName('update')));
        $this->assertInstanceOf(DeleteBaptism::class, app(Baptism::getContractName('delete')));
    }

    public function test_baptism_can_be_created_via_action(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $baptism = app(Baptism::getContractName('create'))->create($user, []);

        $this->assertInstanceOf(Baptism::class, $baptism);
        Event::assertDispatched(CreatedBaptism::class);
    }

    public function test_baptism_can_be_updated_via_action(): void
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

    public function test_baptism_can_be_deleted_via_action(): void
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
