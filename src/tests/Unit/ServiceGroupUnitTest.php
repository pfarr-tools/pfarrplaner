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

use App\Events\Models\ServiceGroup\CreatedServiceGroup;
use App\Events\Models\ServiceGroup\DeletedServiceGroup;
use App\Events\Models\ServiceGroup\UpdatedServiceGroup;
use App\Models\AbstractModel;
use App\Models\People\User;
use App\Models\ServiceGroup;
use App\Services\RoleService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ServiceGroupUnitTest extends TestCase
{
    private User $user;

    public function testServiceGroupCanBeCreatedViaFactory(): void
    {
        $group = ServiceGroup::factory()->create();
        $this->assertCount(1, ServiceGroup::all());
    }

    public function testServiceGroupExtendsAbstractModel(): void
    {
        $this->assertTrue(is_subclass_of(ServiceGroup::class, AbstractModel::class));
    }

    public function testServiceGroupControllerClassesExist(): void
    {
        $this->assertTrue(is_subclass_of(ServiceGroup::controllerClass(), \App\Http\Controllers\AbstractCRUDController::class));
        $this->assertTrue(is_subclass_of(ServiceGroup::apiControllerClass(), \App\Http\Controllers\Api\AbstractApiCRUDController::class));
    }

    public function testServiceGroupContractsResolve(): void
    {
        $this->assertInstanceOf(\App\Actions\ServiceGroup\CreateServiceGroup::class, app(ServiceGroup::getContractName('create')));
        $this->assertInstanceOf(\App\Actions\ServiceGroup\UpdateServiceGroup::class, app(ServiceGroup::getContractName('update')));
        $this->assertInstanceOf(\App\Actions\ServiceGroup\DeleteServiceGroup::class, app(ServiceGroup::getContractName('delete')));
    }

    public function testServiceGroupCanBeCreatedViaAction(): void
    {
        Event::fake();

        $group = app(ServiceGroup::getContractName('create'))->create($this->user, ['name' => 'Musikteam']);

        $this->assertSame('Musikteam', $group->name);
        $this->assertCount(1, ServiceGroup::all());
        Event::assertDispatched(CreatedServiceGroup::class);
    }

    public function testServiceGroupCanBeUpdatedViaAction(): void
    {
        Event::fake();

        $group = ServiceGroup::factory()->create(['name' => 'Alt']);
        $updated = app(ServiceGroup::getContractName('update'))->update($this->user, $group, ['name' => 'Neu']);

        $this->assertSame('Neu', $updated->name);
        Event::assertDispatched(UpdatedServiceGroup::class);
    }

    public function testServiceGroupCanBeDeletedViaAction(): void
    {
        Event::fake();

        $group = ServiceGroup::factory()->create();
        $result = app(ServiceGroup::getContractName('delete'))->delete($this->user, $group);

        $this->assertTrue($result);
        $this->assertCount(0, ServiceGroup::all());
        Event::assertDispatched(DeletedServiceGroup::class);
    }

    public function testCreateIfMissingReusesExistingGroup(): void
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
