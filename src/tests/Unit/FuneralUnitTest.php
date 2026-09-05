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

use App\Events\Models\Funeral\CreatedFuneral;
use App\Events\Models\Funeral\DeletedFuneral;
use App\Events\Models\Funeral\UpdatedFuneral;
use App\Models\AbstractModel;
use App\Models\People\User;
use App\Models\Rites\Funeral;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class FuneralUnitTest extends TestCase
{
    public function testFuneralHasServiceRelationship(): void
    {
        $funeral = Funeral::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $funeral->service());
    }

    public function testFuneralCanBeCreatedViaFactory(): void
    {
        $funeral = Funeral::factory()->create();
        $this->assertCount(1, Funeral::all());
        $this->assertNotNull($funeral->service_id);
    }

    public function testFuneralExtendsAbstractModel(): void
    {
        $this->assertTrue(is_subclass_of(Funeral::class, AbstractModel::class));
    }

    public function testFuneralControllerClassExists(): void
    {
        $this->assertTrue(is_subclass_of(Funeral::controllerClass(), \App\Http\Controllers\AbstractCRUDController::class));
    }

    public function testFuneralContractsResolve(): void
    {
        $this->assertInstanceOf(\App\Actions\Funeral\CreateFuneral::class, app(Funeral::getContractName('create')));
        $this->assertInstanceOf(\App\Actions\Funeral\UpdateFuneral::class, app(Funeral::getContractName('update')));
        $this->assertInstanceOf(\App\Actions\Funeral\DeleteFuneral::class, app(Funeral::getContractName('delete')));
    }

    public function testFuneralCanBeCreatedViaAction(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $service = Service::factory()->create();

        $funeral = app(Funeral::getContractName('create'))->create($user, ['service' => $service->id]);

        $this->assertInstanceOf(Funeral::class, $funeral);
        $this->assertSame($service->id, $funeral->service_id);
        Event::assertDispatched(CreatedFuneral::class);
    }

    public function testFuneralCanBeUpdatedViaAction(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $funeral = Funeral::factory()->create();

        $updated = app(Funeral::getContractName('update'))->update($user, $funeral, [
            'buried_name' => 'Karl Otto',
            'service_id' => $funeral->service_id,
        ]);

        $this->assertSame('Karl Otto', $updated->buried_name);
        Event::assertDispatched(UpdatedFuneral::class);
    }

    public function testFuneralCanBeDeletedViaAction(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $funeral = Funeral::factory()->create();

        $result = app(Funeral::getContractName('delete'))->delete($user, $funeral);

        $this->assertTrue($result);
        $this->assertCount(0, Funeral::all());
        Event::assertDispatched(DeletedFuneral::class);
    }
}
