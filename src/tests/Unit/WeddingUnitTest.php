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

use App\Actions\Wedding\CreateWedding;
use App\Actions\Wedding\DeleteWedding;
use App\Actions\Wedding\UpdateWedding;
use App\Events\Models\Wedding\CreatedWedding;
use App\Events\Models\Wedding\DeletedWedding;
use App\Events\Models\Wedding\UpdatedWedding;
use App\Http\Controllers\AbstractCRUDController;
use App\Models\AbstractModel;
use App\Models\People\User;
use App\Models\Rites\Wedding;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WeddingUnitTest extends TestCase
{
    public function test_wedding_has_service_relationship(): void
    {
        $wedding = Wedding::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $wedding->service());
    }

    public function test_wedding_can_be_created_via_factory(): void
    {
        $wedding = Wedding::factory()->create();
        $this->assertCount(1, Wedding::all());
        $this->assertNotNull($wedding->service_id);
    }

    public function test_wedding_extends_abstract_model(): void
    {
        $this->assertTrue(is_subclass_of(Wedding::class, AbstractModel::class));
    }

    public function test_wedding_controller_class_exists(): void
    {
        $this->assertTrue(is_subclass_of(Wedding::controllerClass(), AbstractCRUDController::class));
    }

    public function test_wedding_contracts_resolve(): void
    {
        $this->assertInstanceOf(CreateWedding::class, app(Wedding::getContractName('create')));
        $this->assertInstanceOf(UpdateWedding::class, app(Wedding::getContractName('update')));
        $this->assertInstanceOf(DeleteWedding::class, app(Wedding::getContractName('delete')));
    }

    public function test_wedding_can_be_created_via_action(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $service = Service::factory()->create();

        $wedding = app(Wedding::getContractName('create'))->create($user, ['service' => $service->id]);

        $this->assertInstanceOf(Wedding::class, $wedding);
        $this->assertSame($service->id, $wedding->service_id);
        Event::assertDispatched(CreatedWedding::class);
    }

    public function test_wedding_can_be_updated_via_action(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $wedding = Wedding::factory()->create();

        $updated = app(Wedding::getContractName('update'))->update($user, $wedding, [
            'spouse1_name' => 'Anna Beispiel',
            'spouse2_name' => $wedding->spouse2_name,
            'service_id' => $wedding->service_id,
        ]);

        $this->assertSame('Anna Beispiel', $updated->spouse1_name);
        Event::assertDispatched(UpdatedWedding::class);
    }

    public function test_wedding_can_be_deleted_via_action(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $wedding = Wedding::factory()->create();

        $result = app(Wedding::getContractName('delete'))->delete($user, $wedding);

        $this->assertTrue($result);
        $this->assertCount(0, Wedding::all());
        Event::assertDispatched(DeletedWedding::class);
    }
}
