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

    public function testIdentifyPersonFindsExistingUserByExactName()
    {
        $user = User::factory()->create(['first_name' => 'Eva', 'last_name' => 'Lehmann']);

        $result = NameService::identifyPerson('Eva Lehmann');

        $this->assertSame(NameService::IDENTIFIED_USER, $result['status']);
        $this->assertSame($user->id, $result['user']->id);
    }

    public function testIdentifyPersonFindsExistingUserByCommaNameWithTitle()
    {
        $user = User::factory()->create(['first_name' => 'Eva', 'last_name' => 'Lehmann', 'title' => 'Dr.']);

        $result = NameService::identifyPerson('Lehmann, Dr. Eva');

        $this->assertSame(NameService::IDENTIFIED_USER, $result['status']);
        $this->assertSame($user->id, $result['user']->id);
    }

    public function testIdentifyPersonFallsBackToNamePairForUnknownPerson()
    {
        $result = NameService::identifyPerson('Petra Beispiel');

        $this->assertSame(NameService::IDENTIFIED_NAME, $result['status']);
        $this->assertSame('Petra', $result['first_name']);
        $this->assertSame('Beispiel', $result['last_name']);
        $this->assertNull($result['user']);
    }

    public function testIdentifyPersonFallsBackToNamePairForDuplicateUsers()
    {
        User::factory()->create(['first_name' => 'Max', 'last_name' => 'Mustermann']);
        User::factory()->create(['first_name' => 'Max', 'last_name' => 'Mustermann']);

        $result = NameService::identifyPerson('Max Mustermann');

        $this->assertSame(NameService::IDENTIFIED_NAME, $result['status']);
        $this->assertSame('Max', $result['first_name']);
        $this->assertSame('Mustermann', $result['last_name']);
        $this->assertNull($result['user']);
    }

    public function testIdentifyPersonMarksSingleWordAsUnidentified()
    {
        $result = NameService::identifyPerson('Küsterdienst');

        $this->assertSame(NameService::UNIDENTIFIED, $result['status']);
        $this->assertSame('', $result['first_name']);
        $this->assertSame('', $result['last_name']);
        $this->assertNull($result['user']);
    }

    public function testIdentifyPeopleGroupsResultsByStatus()
    {
        $user = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Müller']);

        $result = NameService::identifyPeople([
            'Anna Müller',
            'Petra Beispiel',
            'Küsterdienst',
        ]);

        $this->assertCount(1, $result['identified_users']);
        $this->assertSame($user->id, $result['identified_users'][0]['user']->id);
        $this->assertCount(1, $result['identified_names']);
        $this->assertSame('Petra', $result['identified_names'][0]['first_name']);
        $this->assertCount(1, $result['unidentified']);
        $this->assertSame('Küsterdienst', $result['unidentified'][0]['input']);
    }

    public function testIdentifyPeopleUsesContextForSurnameOnlyEntries()
    {
        $result = NameService::identifyPeople([
            'Wolfgang Amann',
            'Amann',
        ]);

        $this->assertCount(2, $result['identified_names']);
        $this->assertSame('Wolfgang', $result['identified_names'][1]['first_name']);
        $this->assertSame('Amann', $result['identified_names'][1]['last_name']);
    }

    public function testIdentifyPeopleUsesContextForInitialAndSurname()
    {
        $result = NameService::identifyPeople([
            'Thomas Will',
            'T.Will',
        ]);

        $this->assertCount(2, $result['identified_names']);
        $this->assertSame('Thomas', $result['identified_names'][1]['first_name']);
        $this->assertSame('Will', $result['identified_names'][1]['last_name']);
    }

    public function testIdentifyPeopleUsesContextForFirstNameOnlyEntries()
    {
        $result = NameService::identifyPeople([
            'Sonja Plaumann',
            'Sonja',
        ]);

        $this->assertCount(2, $result['identified_names']);
        $this->assertSame('Sonja', $result['identified_names'][1]['first_name']);
        $this->assertSame('Plaumann', $result['identified_names'][1]['last_name']);
    }

    public function testIdentifyPeopleRejectsCommentEntries()
    {
        $result = NameService::identifyPeople([
            'Wolfgang Amann',
            'auf Familienzentrum gebucht, da das Opfer für Fam.zentrum abgekündigt',
        ]);

        $this->assertCount(1, $result['identified_names']);
        $this->assertCount(1, $result['unidentified']);
    }
}
