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

use App\Actions\StreetRange\CreateStreetRange;
use App\Actions\StreetRange\DeleteStreetRange;
use App\Actions\StreetRange\UpdateStreetRange;
use App\Events\Models\StreetRange\CreatedStreetRange;
use App\Events\Models\StreetRange\DeletedStreetRange;
use App\Events\Models\StreetRange\UpdatedStreetRange;
use App\Http\Controllers\AbstractCRUDController;
use App\Http\Controllers\Api\AbstractApiCRUDController;
use App\Models\AbstractModel;
use App\Models\Parish;
use App\Models\People\User;
use App\Models\Places\StreetRange;
use App\Services\RoleService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StreetRangeUnitTest extends TestCase
{
    private User $user;

    public function test_street_range_can_be_created_via_factory(): void
    {
        $streetRange = StreetRange::factory()->create();
        $this->assertCount(1, StreetRange::all());
    }

    public function test_street_range_extends_abstract_model(): void
    {
        $this->assertTrue(is_subclass_of(StreetRange::class, AbstractModel::class));
    }

    public function test_street_range_controller_classes_exist(): void
    {
        $this->assertTrue(is_subclass_of(StreetRange::controllerClass(), AbstractCRUDController::class));
        $this->assertTrue(is_subclass_of(StreetRange::apiControllerClass(), AbstractApiCRUDController::class));
    }

    public function test_street_range_contracts_resolve(): void
    {
        $this->assertInstanceOf(CreateStreetRange::class, app(StreetRange::getContractName('create')));
        $this->assertInstanceOf(UpdateStreetRange::class, app(StreetRange::getContractName('update')));
        $this->assertInstanceOf(DeleteStreetRange::class, app(StreetRange::getContractName('delete')));
    }

    public function test_street_range_can_be_created_via_action(): void
    {
        Event::fake();

        $parish = Parish::factory()->create();
        $streetRange = app(StreetRange::getContractName('create'))->create($this->user, $parish, [
            'parish_id' => $parish->id,
            'name' => 'Hauptstraße',
            'odd_start' => 1,
            'odd_end' => 9,
            'even_start' => 2,
            'even_end' => 10,
        ]);

        $this->assertSame('Hauptstraße', $streetRange->name);
        $this->assertCount(1, StreetRange::all());
        Event::assertDispatched(CreatedStreetRange::class);
    }

    public function test_street_range_can_be_updated_via_action(): void
    {
        Event::fake();

        $streetRange = StreetRange::factory()->create();
        $updated = app(StreetRange::getContractName('update'))->update($this->user, $streetRange, [
            'name' => 'Bahnhofstraße',
            'odd_start' => 11,
            'odd_end' => 21,
            'even_start' => 12,
            'even_end' => 22,
        ]);

        $this->assertSame('Bahnhofstraße', $updated->name);
        Event::assertDispatched(UpdatedStreetRange::class);
    }

    public function test_street_range_can_be_deleted_via_action(): void
    {
        Event::fake();

        $streetRange = StreetRange::factory()->create();
        $result = app(StreetRange::getContractName('delete'))->delete($this->user, $streetRange);

        $this->assertTrue($result);
        $this->assertCount(0, StreetRange::all());
        Event::assertDispatched(DeletedStreetRange::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }
}
