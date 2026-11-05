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

use App\Actions\ServiceGroup\CreateServiceGroup;
use App\Actions\ServiceGroup\DeleteServiceGroup;
use App\Actions\ServiceGroup\UpdateServiceGroup;
use App\Events\Models\ServiceGroup\CreatedServiceGroup;
use App\Events\Models\ServiceGroup\DeletedServiceGroup;
use App\Events\Models\ServiceGroup\UpdatedServiceGroup;
use App\Http\Controllers\AbstractCRUDController;
use App\Http\Controllers\Api\AbstractApiCRUDController;
use App\Models\AbstractModel;
use App\Models\People\User;
use App\Models\ServiceGroup;
use App\Services\RoleService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ServiceGroupUnitTest extends TestCase
{
    private User $user;

    public function test_service_group_can_be_created_via_factory(): void
    {
        $group = ServiceGroup::factory()->create();
        $this->assertCount(1, ServiceGroup::all());
    }

    public function test_service_group_extends_abstract_model(): void
    {
        $this->assertTrue(is_subclass_of(ServiceGroup::class, AbstractModel::class));
    }

    public function test_service_group_controller_classes_exist(): void
    {
        $this->assertTrue(is_subclass_of(ServiceGroup::controllerClass(), AbstractCRUDController::class));
        $this->assertTrue(is_subclass_of(ServiceGroup::apiControllerClass(), AbstractApiCRUDController::class));
    }

    public function test_service_group_contracts_resolve(): void
    {
        $this->assertInstanceOf(CreateServiceGroup::class, app(ServiceGroup::getContractName('create')));
        $this->assertInstanceOf(UpdateServiceGroup::class, app(ServiceGroup::getContractName('update')));
        $this->assertInstanceOf(DeleteServiceGroup::class, app(ServiceGroup::getContractName('delete')));
    }

    public function test_service_group_can_be_created_via_action(): void
    {
        Event::fake();

        $group = app(ServiceGroup::getContractName('create'))->create($this->user, ['name' => 'Musikteam']);

        $this->assertSame('Musikteam', $group->name);
        $this->assertCount(1, ServiceGroup::all());
        Event::assertDispatched(CreatedServiceGroup::class);
    }

    public function test_service_group_can_be_updated_via_action(): void
    {
        Event::fake();

        $group = ServiceGroup::factory()->create(['name' => 'Alt']);
        $updated = app(ServiceGroup::getContractName('update'))->update($this->user, $group, ['name' => 'Neu']);

        $this->assertSame('Neu', $updated->name);
        Event::assertDispatched(UpdatedServiceGroup::class);
    }

    public function test_service_group_can_be_deleted_via_action(): void
    {
        Event::fake();

        $group = ServiceGroup::factory()->create();
        $result = app(ServiceGroup::getContractName('delete'))->delete($this->user, $group);

        $this->assertTrue($result);
        $this->assertCount(0, ServiceGroup::all());
        Event::assertDispatched(DeletedServiceGroup::class);
    }

    public function test_create_if_missing_reuses_existing_group(): void
    {
        $group = ServiceGroup::factory()->create(['name' => 'Lektor:innen']);

        $ids = ServiceGroup::createIfMissing(['Lektor:innen', $group->id]);

        $this->assertSame([$group->id, $group->id], $ids);
        $this->assertCount(1, ServiceGroup::all());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }
}
