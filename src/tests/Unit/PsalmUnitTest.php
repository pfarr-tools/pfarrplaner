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

use App\Actions\Psalm\CreatePsalm;
use App\Actions\Psalm\DeletePsalm;
use App\Actions\Psalm\UpdatePsalm;
use App\Events\Models\Psalm\CreatedPsalm;
use App\Events\Models\Psalm\DeletedPsalm;
use App\Events\Models\Psalm\UpdatedPsalm;
use App\Http\Controllers\AbstractCRUDController;
use App\Http\Controllers\Api\AbstractApiCRUDController;
use App\Models\AbstractModel;
use App\Models\Liturgy\Psalm;
use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PsalmUnitTest extends TestCase
{
    public function test_psalm_can_be_created_via_factory(): void
    {
        $psalm = Psalm::factory()->create();
        $this->assertCount(1, Psalm::all());
        $this->assertNotEmpty($psalm->title);
    }

    public function test_psalm_title_is_required(): void
    {
        $psalm = Psalm::factory()->create(['title' => 'Test Psalm']);
        $this->assertEquals('Test Psalm', $psalm->fresh()->title);
    }

    public function test_psalm_extends_abstract_model(): void
    {
        $this->assertTrue(is_subclass_of(Psalm::class, AbstractModel::class));
    }

    public function test_psalm_controller_classes_exist(): void
    {
        $this->assertTrue(is_subclass_of(Psalm::controllerClass(), AbstractCRUDController::class));
        $this->assertTrue(is_subclass_of(Psalm::apiControllerClass(), AbstractApiCRUDController::class));
    }

    public function test_psalm_contracts_resolve(): void
    {
        $this->assertInstanceOf(CreatePsalm::class, app(Psalm::getContractName('create')));
        $this->assertInstanceOf(UpdatePsalm::class, app(Psalm::getContractName('update')));
        $this->assertInstanceOf(DeletePsalm::class, app(Psalm::getContractName('delete')));
    }

    public function test_psalm_can_be_created_via_action(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $psalm = app(Psalm::getContractName('create'))->create($user, ['title' => 'Psalm 23']);

        $this->assertSame('Psalm 23', $psalm->title);
        Event::assertDispatched(CreatedPsalm::class);
    }

    public function test_psalm_can_be_updated_via_action(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $psalm = Psalm::factory()->create(['title' => 'Alt']);

        $updated = app(Psalm::getContractName('update'))->update($user, $psalm, ['title' => 'Neu']);

        $this->assertSame('Neu', $updated->title);
        Event::assertDispatched(UpdatedPsalm::class);
    }

    public function test_psalm_can_be_deleted_via_action(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $psalm = Psalm::factory()->create();

        $result = app(Psalm::getContractName('delete'))->delete($user, $psalm);

        $this->assertTrue($result);
        $this->assertCount(0, Psalm::all());
        Event::assertDispatched(DeletedPsalm::class);
    }
}
