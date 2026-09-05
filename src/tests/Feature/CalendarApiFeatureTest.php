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

use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\CalendarService;
use App\Services\LiturgyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CalendarApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // LiturgyService fetches liturgical calendar from storage; fake the disk to avoid external HTTP calls.
        Storage::fake();
        foreach (range((int)date('Y') - 2, (int)date('Y') + 2) as $year) {
            Storage::put("liturgy/{$year}.json", json_encode(['Tage' => []]));
        }
        Storage::put('liturgy/.json', json_encode(['Tage' => []]));

        $calendarsRef = new \ReflectionProperty(LiturgyService::class, 'calendars');
        $calendarsRef->setAccessible(true);
        $calendarsRef->setValue(null, []);

        foreach (['gd-bearbeiten', 'gd-allgemein-bearbeiten', 'gd-opfer-bearbeiten'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }
    }

    /**
     * @return void
     */
    public function testMonthReturnsCalendarData()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city->id);

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.calendar.month', ['date' => '2024-01']));

        $response->assertOk();
    }

    /**
     * @return void
     */
    public function testMonthEmbedsCompactServiceDataForVisibleCities()
    {
        $city = City::factory()->create(['name' => 'Musterstadt']);
        $relatedCity = City::factory()->create(['name' => 'Tochtergemeinde']);
        $user = User::factory()->create();
        $user->cities()->attach([$city->id, $relatedCity->id]);

        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => '2024-01-14 10:00:00',
            'title' => 'Abendgottesdienst',
            'cc' => 1,
            'cc_lesson' => 'Barmherzigkeit',
            'cc_staff' => 'Team A',
        ]);
        $service->participants()->attach($user->id, ['category' => 'P']);
        $service->relatedCities()->attach($relatedCity->id);
        $service->refresh();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.calendar.month', ['date' => '2024-01']));

        $response->assertOk();
        $response->assertJsonPath('loadedDate', '2024-01');
        $response->assertJsonPath('data.2024-01-14.services.'.$city->id.'.0.id', $service->id);
        $response->assertJsonPath('data.2024-01-14.services.'.$relatedCity->id.'.0.id', $service->id);
        $response->assertJsonPath('data.2024-01-14.services.'.$city->id.'.0.participantText.P', $user->name);
        $response->assertJsonPath('data.2024-01-14.services.'.$city->id.'.0.cc_lesson', 'Barmherzigkeit');
        $response->assertJsonMissingPath('data.2024-01-14.services.'.$city->id.'.0.pastors');
    }

    /**
     * @return void
     */
    public function testMonthRequiresAuth()
    {
        $response = $this->getJson(route('api.calendar.month', ['date' => '2024-01']));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testServiceRequiresAuth()
    {
        $service = Service::factory()->create();

        $response = $this->getJson(route('api.calendar.service', $service));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testQuickPickReturnsServices()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city->id);

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.calendar.quick-pick', ['date' => '01.01.2024']));

        $response->assertOk();
    }

    /**
     * @return void
     */
    public function testQuickPickRequiresAuth()
    {
        $response = $this->getJson(route('api.calendar.quick-pick', ['date' => '01.01.2024']));

        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testBuildMonthPayloadContainsEmbeddedCalendarData()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city->id);
        Service::factory()->create([
            'city_id' => $city->id,
            'date' => '2024-01-21 09:30:00',
        ]);

        $payload = CalendarService::buildMonthPayload(\Carbon\Carbon::parse('2024-01-01 00:00:00'), $user);

        $this->assertSame('2024-01', $payload['loadedDate']);
        $this->assertArrayHasKey('2024-01-21', $payload['data']);
        $this->assertArrayHasKey($city->id, $payload['data']['2024-01-21']['services']);
    }

    /**
     * @return void
     */
    public function testMonthOnlyReturnsServiceEvents()
    {
        $city = City::factory()->create();
        $user = User::factory()->create();
        $user->cities()->attach($city->id);

        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => '2024-01-14 10:00:00',
            'event_class' => 'service',
            'title' => 'Gottesdienst',
        ]);

        Service::factory()->create([
            'city_id' => $city->id,
            'date' => '2024-01-15 19:00:00',
            'event_class' => 'event',
            'title' => 'Konzert',
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.calendar.month', ['date' => '2024-01']));

        $response->assertOk();
        $response->assertJsonPath('data.2024-01-14.services.'.$city->id.'.0.id', $service->id);
        $response->assertJsonMissingPath('data.2024-01-15.services.'.$city->id.'.0');
    }
}
