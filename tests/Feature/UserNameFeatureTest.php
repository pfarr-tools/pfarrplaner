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

namespace Tests\Feature;

use App\Http\Resources\UserResource;
use App\Models\People\User;
use App\Services\NameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNameFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function testNameServiceFromUserUsesFirstAndLastName()
    {
        $user = User::factory()->make(['first_name' => 'Anna', 'last_name' => 'Müller', 'title' => '']);
        $ns = NameService::fromUser($user);
        $this->assertSame('Anna', $ns->getFirstName());
        $this->assertSame('Müller', $ns->getLastName());
    }

    public function testNameServiceFromUserHandlesNullNames()
    {
        $user = User::factory()->make(['first_name' => null, 'last_name' => 'Admin', 'title' => null]);
        $ns = NameService::fromUser($user);
        $this->assertSame('', $ns->getFirstName());
        $this->assertSame('Admin', $ns->getLastName());
    }

    public function testNameServiceFromUserIncludesTitle()
    {
        $user = User::factory()->make(['first_name' => 'Paul', 'last_name' => 'Fischer', 'title' => 'Dr.']);
        $ns = NameService::fromUser($user);
        $this->assertSame('Dr.', $ns->getTitle());
        $this->assertSame('Dr. Paul Fischer', $ns->format(NameService::TITLE_FIRST_LAST));
    }

    public function testFullNameReturnsTrimmedFirstLast()
    {
        $user = User::factory()->make(['first_name' => 'Maria', 'last_name' => 'Schmidt']);
        $this->assertSame('Maria Schmidt', $user->fullName());
    }

    public function testFullNameWithTitleReturnsTitleFirstLast()
    {
        $user = User::factory()->make(['first_name' => 'Hans', 'last_name' => 'Weber', 'title' => 'Pfarrer']);
        $this->assertSame('Pfarrer Hans Weber', $user->fullName(true));
    }

    public function testFullNameWithOnlyLastName()
    {
        $user = User::factory()->make(['first_name' => null, 'last_name' => 'Admin']);
        $this->assertSame('Admin', $user->fullName());
    }

    public function testUserResourceNameFieldUsesFullName()
    {
        $user = User::factory()->create(['first_name' => 'Lena', 'last_name' => 'Bauer']);
        $resource = (new UserResource($user))->resolve();
        $this->assertSame('Lena Bauer', $resource['name']);
    }

    public function testCreateIfNotExistsDoesNotSetName()
    {
        $id = User::createIfNotExists('Max Mustermann');
        $user = User::find($id);
        $this->assertNotNull($user);
        $this->assertSame('Max', $user->first_name);
        $this->assertSame('Mustermann', $user->last_name);
    }

    public function testCreateIfNotExistsSingleWordGoesToLastName()
    {
        $id = User::createIfNotExists('Mustermann');
        $user = User::find($id);
        $this->assertSame('Mustermann', $user->last_name);
        $this->assertSame('', $user->first_name);
    }

    public function testCreateIfNotExistsTitleFirstLast()
    {
        $id = User::createIfNotExists('Dr. Eva Lehmann');
        $user = User::find($id);
        $this->assertSame('Dr.', $user->title);
        $this->assertSame('Eva', $user->first_name);
        $this->assertSame('Lehmann', $user->last_name);
    }
}
