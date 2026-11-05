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

use App\Actions\Songbook\CreateSongbook;
use App\Actions\Songbook\DeleteSongbook;
use App\Actions\Songbook\UpdateSongbook;
use App\Events\Models\Songbook\CreatedSongbook;
use App\Events\Models\Songbook\DeletedSongbook;
use App\Events\Models\Songbook\UpdatedSongbook;
use App\Http\Controllers\AbstractCRUDController;
use App\Http\Controllers\Api\AbstractApiCRUDController;
use App\Models\AbstractModel;
use App\Models\Liturgy\Songbook;
use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SongbookUnitTest extends TestCase
{
    public function test_songbook_has_songs_relationship(): void
    {
        $songbook = Songbook::factory()->create();
        $this->assertInstanceOf(BelongsToMany::class, $songbook->songs());
    }

    public function test_songbook_can_be_created_via_factory(): void
    {
        $songbook = Songbook::factory()->create();
        $this->assertCount(1, Songbook::all());
        $this->assertNotEmpty($songbook->name);
    }

    public function test_songbook_extends_abstract_model(): void
    {
        $this->assertTrue(is_subclass_of(Songbook::class, AbstractModel::class));
    }

    public function test_songbook_controller_classes_exist(): void
    {
        $this->assertTrue(is_subclass_of(Songbook::controllerClass(), AbstractCRUDController::class));
        $this->assertTrue(is_subclass_of(Songbook::apiControllerClass(), AbstractApiCRUDController::class));
    }

    public function test_songbook_contracts_resolve(): void
    {
        $this->assertInstanceOf(CreateSongbook::class, app(Songbook::getContractName('create')));
        $this->assertInstanceOf(UpdateSongbook::class, app(Songbook::getContractName('update')));
        $this->assertInstanceOf(DeleteSongbook::class, app(Songbook::getContractName('delete')));
    }

    public function test_songbook_can_be_created_via_action(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $songbook = app(Songbook::getContractName('create'))->create($user, [
            'name' => 'Evangelisches Gesangbuch',
            'code' => 'EG',
        ]);

        $this->assertSame('EG', $songbook->code);
        Event::assertDispatched(CreatedSongbook::class);
    }

    public function test_songbook_can_be_updated_via_action(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $songbook = Songbook::factory()->create(['name' => 'Alt']);

        $updated = app(Songbook::getContractName('update'))->update($user, $songbook, [
            'name' => 'Neu',
            'code' => $songbook->code,
        ]);

        $this->assertSame('Neu', $updated->name);
        Event::assertDispatched(UpdatedSongbook::class);
    }

    public function test_songbook_can_be_deleted_via_action(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $songbook = Songbook::factory()->create();

        $result = app(Songbook::getContractName('delete'))->delete($user, $songbook);

        $this->assertTrue($result);
        $this->assertCount(0, Songbook::all());
        Event::assertDispatched(DeletedSongbook::class);
    }
}
