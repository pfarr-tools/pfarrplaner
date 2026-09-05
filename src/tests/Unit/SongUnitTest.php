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

use App\Events\Models\Song\CreatedSong;
use App\Events\Models\Song\DeletedSong;
use App\Events\Models\Song\UpdatedSong;
use App\Models\AbstractModel;
use App\Models\Liturgy\Song;
use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SongUnitTest extends TestCase
{
    public function testSongHasVersesRelationship(): void
    {
        $song = Song::factory()->create();
        $this->assertInstanceOf(HasMany::class, $song->verses());
    }

    public function testSongHasSongbooksRelationship(): void
    {
        $song = Song::factory()->create();
        $this->assertInstanceOf(BelongsToMany::class, $song->songbooks());
    }

    public function testSongCanBeCreatedViaFactory(): void
    {
        $song = Song::factory()->create();
        $this->assertCount(1, Song::all());
        $this->assertNotEmpty($song->title);
    }

    public function testSongExtendsAbstractModel(): void
    {
        $this->assertTrue(is_subclass_of(Song::class, AbstractModel::class));
    }

    public function testSongControllerClassesExist(): void
    {
        $this->assertTrue(is_subclass_of(Song::controllerClass(), \App\Http\Controllers\AbstractCRUDController::class));
        $this->assertTrue(is_subclass_of(Song::apiControllerClass(), \App\Http\Controllers\Api\AbstractApiCRUDController::class));
    }

    public function testSongContractsResolve(): void
    {
        $this->assertInstanceOf(\App\Actions\Song\CreateSong::class, app(Song::getContractName('create')));
        $this->assertInstanceOf(\App\Actions\Song\UpdateSong::class, app(Song::getContractName('update')));
        $this->assertInstanceOf(\App\Actions\Song\DeleteSong::class, app(Song::getContractName('delete')));
    }

    public function testSongCanBeCreatedViaAction(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $song = app(Song::getContractName('create'))->create($user, [
            'title' => 'Testlied',
            'verses' => [['number' => '1', 'text' => 'Strophe 1', 'refrain_before' => false, 'refrain_after' => false]],
            'songbooks' => [],
        ]);

        $this->assertSame('Testlied', $song->title);
        $this->assertCount(1, $song->verses);
        Event::assertDispatched(CreatedSong::class);
    }

    public function testSongCanBeUpdatedViaAction(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $song = Song::factory()->create(['title' => 'Alt']);

        $updated = app(Song::getContractName('update'))->update($user, $song, [
            'title' => 'Neu',
            'verses' => [['number' => '1', 'text' => 'Neue Strophe', 'refrain_before' => false, 'refrain_after' => false]],
            'songbooks' => [],
        ]);

        $this->assertSame('Neu', $updated->title);
        $this->assertCount(1, $updated->verses);
        Event::assertDispatched(UpdatedSong::class);
    }

    public function testSongCanBeDeletedViaAction(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $song = Song::factory()->create();

        $result = app(Song::getContractName('delete'))->delete($user, $song);

        $this->assertTrue($result);
        $this->assertCount(0, Song::all());
        Event::assertDispatched(DeletedSong::class);
    }
}
