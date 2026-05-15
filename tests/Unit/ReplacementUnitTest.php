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

use App\Events\Models\Replacement\CreatedReplacement;
use App\Events\Models\Replacement\DeletedReplacement;
use App\Events\Models\Replacement\UpdatedReplacement;
use App\Models\AbstractModel;
use App\Models\Leave\Absence;
use App\Models\Leave\Replacement;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReplacementUnitTest extends TestCase
{
    public function testReplacementCanBeCreatedViaFactory(): void
    {
        $replacement = Replacement::factory()->create();
        $this->assertCount(1, Replacement::all());
    }

    public function testReplacementExtendsAbstractModel(): void
    {
        $this->assertTrue(is_subclass_of(Replacement::class, AbstractModel::class));
    }

    public function testReplacementControllerClassesExist(): void
    {
        $this->assertTrue(is_subclass_of(Replacement::controllerClass(), \App\Http\Controllers\AbstractCRUDController::class));
        $this->assertTrue(is_subclass_of(Replacement::apiControllerClass(), \App\Http\Controllers\Api\AbstractApiCRUDController::class));
    }

    public function testReplacementContractsResolve(): void
    {
        $this->assertInstanceOf(\App\Actions\Replacement\CreateReplacement::class, app(Replacement::getContractName('create')));
        $this->assertInstanceOf(\App\Actions\Replacement\UpdateReplacement::class, app(Replacement::getContractName('update')));
        $this->assertInstanceOf(\App\Actions\Replacement\DeleteReplacement::class, app(Replacement::getContractName('delete')));
    }

    public function testReplacementHasAbsenceRelationship(): void
    {
        $replacement = Replacement::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $replacement->absence());
    }

    public function testReplacementCanBeCreatedViaAction(): void
    {
        Event::fake();

        $absence = Absence::factory()->create();
        $replacement = app(Replacement::getContractName('create'))->create($absence->user, $absence, [
            'absence_id' => $absence->id,
            'from' => now(),
            'to' => now()->addDay(),
            'users' => [],
        ]);

        $this->assertSame($absence->id, $replacement->absence_id);
        Event::assertDispatched(CreatedReplacement::class);
    }

    public function testReplacementCanBeUpdatedViaAction(): void
    {
        Event::fake();

        $replacement = Replacement::factory()->create();
        $updated = app(Replacement::getContractName('update'))->update($replacement->absence->user, $replacement, [
            'from' => now()->addDays(2),
            'to' => now()->addDays(3),
            'pool_id' => null,
            'users' => [],
        ]);

        $this->assertTrue($updated->from->isToday() || $updated->from->isFuture());
        Event::assertDispatched(UpdatedReplacement::class);
    }

    public function testReplacementCanBeDeletedViaAction(): void
    {
        Event::fake();

        $replacement = Replacement::factory()->create();
        $result = app(Replacement::getContractName('delete'))->delete($replacement->absence->user, $replacement);

        $this->assertTrue($result);
        $this->assertCount(0, Replacement::all());
        Event::assertDispatched(DeletedReplacement::class);
    }
}
