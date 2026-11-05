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

use App\Actions\Song\CreateSong;
use App\Actions\Song\DeleteSong;
use App\Actions\Song\UpdateSong;
use App\Events\Models\Song\CreatedSong;
use App\Events\Models\Song\DeletedSong;
use App\Events\Models\Song\UpdatedSong;
use App\Http\Controllers\AbstractCRUDController;
use App\Http\Controllers\Api\AbstractApiCRUDController;
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
    public function test_song_has_verses_relationship(): void
    {
        $song = Song::factory()->create();
        $this->assertInstanceOf(HasMany::class, $song->verses());
    }

    public function test_song_has_songbooks_relationship(): void
    {
        $song = Song::factory()->create();
        $this->assertInstanceOf(BelongsToMany::class, $song->songbooks());
    }

    public function test_song_can_be_created_via_factory(): void
    {
        $song = Song::factory()->create();
        $this->assertCount(1, Song::all());
        $this->assertNotEmpty($song->title);
    }

    public function test_song_extends_abstract_model(): void
    {
        $this->assertTrue(is_subclass_of(Song::class, AbstractModel::class));
    }

    public function test_song_controller_classes_exist(): void
    {
        $this->assertTrue(is_subclass_of(Song::controllerClass(), AbstractCRUDController::class));
        $this->assertTrue(is_subclass_of(Song::apiControllerClass(), AbstractApiCRUDController::class));
    }

    public function test_song_contracts_resolve(): void
    {
        $this->assertInstanceOf(CreateSong::class, app(Song::getContractName('create')));
        $this->assertInstanceOf(UpdateSong::class, app(Song::getContractName('update')));
        $this->assertInstanceOf(DeleteSong::class, app(Song::getContractName('delete')));
    }

    public function test_song_can_be_created_via_action(): void
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

    public function test_song_can_be_updated_via_action(): void
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

    public function test_song_can_be_deleted_via_action(): void
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
