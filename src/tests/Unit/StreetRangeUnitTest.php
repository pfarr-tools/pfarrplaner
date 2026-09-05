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

use App\Events\Models\StreetRange\CreatedStreetRange;
use App\Events\Models\StreetRange\DeletedStreetRange;
use App\Events\Models\StreetRange\UpdatedStreetRange;
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

    public function testStreetRangeCanBeCreatedViaFactory(): void
    {
        $streetRange = StreetRange::factory()->create();
        $this->assertCount(1, StreetRange::all());
    }

    public function testStreetRangeExtendsAbstractModel(): void
    {
        $this->assertTrue(is_subclass_of(StreetRange::class, AbstractModel::class));
    }

    public function testStreetRangeControllerClassesExist(): void
    {
        $this->assertTrue(is_subclass_of(StreetRange::controllerClass(), \App\Http\Controllers\AbstractCRUDController::class));
        $this->assertTrue(is_subclass_of(StreetRange::apiControllerClass(), \App\Http\Controllers\Api\AbstractApiCRUDController::class));
    }

    public function testStreetRangeContractsResolve(): void
    {
        $this->assertInstanceOf(\App\Actions\StreetRange\CreateStreetRange::class, app(StreetRange::getContractName('create')));
        $this->assertInstanceOf(\App\Actions\StreetRange\UpdateStreetRange::class, app(StreetRange::getContractName('update')));
        $this->assertInstanceOf(\App\Actions\StreetRange\DeleteStreetRange::class, app(StreetRange::getContractName('delete')));
    }

    public function testStreetRangeCanBeCreatedViaAction(): void
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

    public function testStreetRangeCanBeUpdatedViaAction(): void
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

    public function testStreetRangeCanBeDeletedViaAction(): void
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
