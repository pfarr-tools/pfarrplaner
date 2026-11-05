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

namespace Tests\Feature;

use App\Http\Resources\UserResource;
use App\Models\People\User;
use App\Services\NameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNameFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_service_from_user_uses_first_and_last_name()
    {
        $user = User::factory()->make(['first_name' => 'Anna', 'last_name' => 'Müller', 'title' => '']);
        $ns = NameService::fromUser($user);
        $this->assertSame('Anna', $ns->getFirstName());
        $this->assertSame('Müller', $ns->getLastName());
    }

    public function test_name_service_from_user_handles_null_names()
    {
        $user = User::factory()->make(['first_name' => null, 'last_name' => 'Admin', 'title' => null]);
        $ns = NameService::fromUser($user);
        $this->assertSame('', $ns->getFirstName());
        $this->assertSame('Admin', $ns->getLastName());
    }

    public function test_name_service_from_user_includes_title()
    {
        $user = User::factory()->make(['first_name' => 'Paul', 'last_name' => 'Fischer', 'title' => 'Dr.']);
        $ns = NameService::fromUser($user);
        $this->assertSame('Dr.', $ns->getTitle());
        $this->assertSame('Dr. Paul Fischer', $ns->format(NameService::TITLE_FIRST_LAST));
    }

    public function test_full_name_returns_trimmed_first_last()
    {
        $user = User::factory()->make(['first_name' => 'Maria', 'last_name' => 'Schmidt']);
        $this->assertSame('Maria Schmidt', $user->fullName());
    }

    public function test_full_name_with_title_returns_title_first_last()
    {
        $user = User::factory()->make(['first_name' => 'Hans', 'last_name' => 'Weber', 'title' => 'Pfarrer']);
        $this->assertSame('Pfarrer Hans Weber', $user->fullName(true));
    }

    public function test_full_name_with_only_last_name()
    {
        $user = User::factory()->make(['first_name' => null, 'last_name' => 'Admin']);
        $this->assertSame('Admin', $user->fullName());
    }

    public function test_user_resource_name_field_uses_full_name()
    {
        $user = User::factory()->create(['first_name' => 'Lena', 'last_name' => 'Bauer']);
        $resource = (new UserResource($user))->resolve();
        $this->assertSame('Lena Bauer', $resource['name']);
    }

    public function test_create_if_not_exists_does_not_set_name()
    {
        $id = User::createIfNotExists('Max Mustermann');
        $user = User::find($id);
        $this->assertNotNull($user);
        $this->assertSame('Max', $user->first_name);
        $this->assertSame('Mustermann', $user->last_name);
    }

    public function test_create_if_not_exists_single_word_goes_to_last_name()
    {
        $id = User::createIfNotExists('Mustermann');
        $user = User::find($id);
        $this->assertSame('Mustermann', $user->last_name);
        $this->assertSame('', $user->first_name);
    }

    public function test_create_if_not_exists_title_first_last()
    {
        $id = User::createIfNotExists('Dr. Eva Lehmann');
        $user = User::find($id);
        $this->assertSame('Dr.', $user->title);
        $this->assertSame('Eva', $user->first_name);
        $this->assertSame('Lehmann', $user->last_name);
    }

    public function test_identify_person_finds_existing_user_by_exact_name()
    {
        $user = User::factory()->create(['first_name' => 'Eva', 'last_name' => 'Lehmann']);

        $result = NameService::identifyPerson('Eva Lehmann');

        $this->assertSame(NameService::IDENTIFIED_USER, $result['status']);
        $this->assertSame($user->id, $result['user']->id);
    }

    public function test_identify_person_finds_existing_user_by_comma_name_with_title()
    {
        $user = User::factory()->create(['first_name' => 'Eva', 'last_name' => 'Lehmann', 'title' => 'Dr.']);

        $result = NameService::identifyPerson('Lehmann, Dr. Eva');

        $this->assertSame(NameService::IDENTIFIED_USER, $result['status']);
        $this->assertSame($user->id, $result['user']->id);
    }

    public function test_identify_person_falls_back_to_name_pair_for_unknown_person()
    {
        $result = NameService::identifyPerson('Petra Beispiel');

        $this->assertSame(NameService::IDENTIFIED_NAME, $result['status']);
        $this->assertSame('Petra', $result['first_name']);
        $this->assertSame('Beispiel', $result['last_name']);
        $this->assertNull($result['user']);
    }

    public function test_identify_person_falls_back_to_name_pair_for_duplicate_users()
    {
        User::factory()->create(['first_name' => 'Max', 'last_name' => 'Mustermann']);
        User::factory()->create(['first_name' => 'Max', 'last_name' => 'Mustermann']);

        $result = NameService::identifyPerson('Max Mustermann');

        $this->assertSame(NameService::IDENTIFIED_NAME, $result['status']);
        $this->assertSame('Max', $result['first_name']);
        $this->assertSame('Mustermann', $result['last_name']);
        $this->assertNull($result['user']);
    }

    public function test_identify_person_marks_single_word_as_unidentified()
    {
        $result = NameService::identifyPerson('Küsterdienst');

        $this->assertSame(NameService::UNIDENTIFIED, $result['status']);
        $this->assertSame('', $result['first_name']);
        $this->assertSame('', $result['last_name']);
        $this->assertNull($result['user']);
    }

    public function test_identify_people_groups_results_by_status()
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

    public function test_identify_people_uses_context_for_surname_only_entries()
    {
        $result = NameService::identifyPeople([
            'Wolfgang Amann',
            'Amann',
        ]);

        $this->assertCount(2, $result['identified_names']);
        $this->assertSame('Wolfgang', $result['identified_names'][1]['first_name']);
        $this->assertSame('Amann', $result['identified_names'][1]['last_name']);
    }

    public function test_identify_people_uses_context_for_initial_and_surname()
    {
        $result = NameService::identifyPeople([
            'Thomas Will',
            'T.Will',
        ]);

        $this->assertCount(2, $result['identified_names']);
        $this->assertSame('Thomas', $result['identified_names'][1]['first_name']);
        $this->assertSame('Will', $result['identified_names'][1]['last_name']);
    }

    public function test_identify_people_uses_context_for_first_name_only_entries()
    {
        $result = NameService::identifyPeople([
            'Sonja Plaumann',
            'Sonja',
        ]);

        $this->assertCount(2, $result['identified_names']);
        $this->assertSame('Sonja', $result['identified_names'][1]['first_name']);
        $this->assertSame('Plaumann', $result['identified_names'][1]['last_name']);
    }

    public function test_identify_people_rejects_comment_entries()
    {
        $result = NameService::identifyPeople([
            'Wolfgang Amann',
            'auf Familienzentrum gebucht, da das Opfer für Fam.zentrum abgekündigt',
        ]);

        $this->assertCount(1, $result['identified_names']);
        $this->assertCount(1, $result['unidentified']);
    }
}
