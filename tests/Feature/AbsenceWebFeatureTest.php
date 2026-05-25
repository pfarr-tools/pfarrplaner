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

use App\Http\Controllers\AbsenceController;
use App\Models\Leave\Absence;
use App\Models\Leave\Pool;
use App\Models\Leave\Poolmaster;
use App\Models\Leave\Replacement;
use App\Models\People\User;
use App\Models\Places\City;
use App\Services\RoleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AbsenceWebFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function testIndexLoads(): void
    {
        $this->actingAs($this->user)
            ->get(route('absences.index', ['year' => date('Y'), 'month' => date('m')]))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Absences/Planner'));
    }

    public function testEditorLoads(): void
    {
        $absence = Absence::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user)
            ->get(route('absence.edit', $absence->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Absences/AbsenceEditor'));
    }

    public function testCreatePersistsSingleDayAbsenceAndRedirectsToEditor(): void
    {
        $this->actingAs($this->user);

        /** @var AbsenceController $controller */
        $controller = app(AbsenceController::class);
        $request = Request::create('/absences/2026/8/'.$this->user->id.'/2/create', 'GET');
        $request->setUserResolver(fn() => $this->user);

        $response = $controller->create($request, 2026, 8, $this->user, 2);

        $absence = Absence::latest('id')->first();

        $this->assertNotNull($absence);
        $this->assertSame($this->user->id, $absence->user_id);
        $this->assertSame('Urlaub', $absence->reason);
        $this->assertSame('2026-08-01 22:00:00', Carbon::parse($absence->from)->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-02 21:59:59', Carbon::parse($absence->to)->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('absence.edit', $absence->id), $response->getTargetUrl());
    }

    public function testCreatePersistsSelectedDateRangeInUtcAndRedirectsToEditor(): void
    {
        $this->actingAs($this->user);

        /** @var AbsenceController $controller */
        $controller = app(AbsenceController::class);
        $request = Request::create('/absences/2026/8/'.$this->user->id.'/2/create', 'GET', [
            'fromDate' => '2026-08-02',
            'toDate' => '2026-08-03',
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $controller->create($request, 2026, 8, $this->user, 2);

        $absence = Absence::latest('id')->first();

        $this->assertNotNull($absence);
        $this->assertSame('2026-08-01 22:00:00', Carbon::parse($absence->from)->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-03 21:59:59', Carbon::parse($absence->to)->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('absence.edit', $absence->id), $response->getTargetUrl());
    }

    public function testPlannerDaysLimitsMultiMonthAbsenceToVisibleMonth(): void
    {
        $city = City::factory()->create();
        $this->user->update(['manage_absences' => 1]);
        $this->user->cities()->attach($city->id, ['permission' => 'a']);
        $this->user->homeCities()->attach($city->id);

        Absence::factory()->create([
            'user_id' => $this->user->id,
            'from' => Carbon::create(2026, 8, 22, 0, 0, 0),
            'to' => Carbon::create(2026, 9, 12, 0, 0, 0),
            'reason' => 'Urlaub',
        ]);

        $this->actingAs($this->user);

        /** @var AbsenceController $controller */
        $controller = app(AbsenceController::class);

        $augustPayload = $controller->days('2026-08', $this->user)->getData(true);
        $this->assertSame(10, $augustPayload[22]['duration']);
        $this->assertTrue($augustPayload[22]['show']);
        $this->assertFalse($augustPayload[31]['show']);

        $septemberPayload = $controller->days('2026-09', $this->user)->getData(true);
        $this->assertSame(12, $septemberPayload[1]['duration']);
        $this->assertTrue($septemberPayload[1]['show']);
        $this->assertFalse($septemberPayload[12]['show']);
    }

    public function testPlannerPrefersRealAbsenceOverOverlappingPoolmasterEntry(): void
    {
        $city = City::factory()->create();
        $pool = Pool::factory()->create(['name' => 'Oberes Gaeu']);
        $this->user->update(['manage_absences' => 1]);
        $this->user->cities()->attach($city->id, ['permission' => 'a']);
        $this->user->homeCities()->attach($city->id);

        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'from' => Carbon::create(2026, 8, 22, 0, 0, 0),
            'to' => Carbon::create(2026, 9, 12, 0, 0, 0),
            'reason' => 'Urlaub',
        ]);

        Poolmaster::factory()->create([
            'pool_id' => $pool->id,
            'user_id' => $this->user->id,
            'start' => '2026-08-22',
            'end' => '2026-08-31',
        ]);

        $this->actingAs($this->user);

        /** @var AbsenceController $controller */
        $controller = app(AbsenceController::class);
        $augustPayload = $controller->days('2026-08', $this->user)->getData(true);

        $this->assertSame($absence->id, $augustPayload[22]['absence']['id']);
        $this->assertSame('Urlaub', $augustPayload[22]['absence']['reason']);
        $this->assertArrayNotHasKey('poolmaster', $augustPayload[22]['absence']);
    }

    public function testPlannerSplitsPoolmasterEntryAroundRealAbsence(): void
    {
        $city = City::factory()->create();
        $pool = Pool::factory()->create(['name' => 'Oberes Gaeu']);
        $this->user->update(['manage_absences' => 1]);
        $this->user->cities()->attach($city->id, ['permission' => 'a']);
        $this->user->homeCities()->attach($city->id);

        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'from' => Carbon::create(2026, 8, 22, 0, 0, 0),
            'to' => Carbon::create(2026, 9, 12, 0, 0, 0),
            'reason' => 'Urlaub',
        ]);

        Poolmaster::factory()->create([
            'pool_id' => $pool->id,
            'user_id' => $this->user->id,
            'start' => '2026-08-01',
            'end' => '2026-08-31',
        ]);

        $this->actingAs($this->user);

        /** @var AbsenceController $controller */
        $controller = app(AbsenceController::class);
        $augustPayload = $controller->days('2026-08', $this->user)->getData(true);

        $this->assertTrue($augustPayload[1]['absence']['poolmaster']);
        $this->assertSame(21, $augustPayload[1]['duration']);
        $this->assertSame($absence->id, $augustPayload[22]['absence']['id']);
        $this->assertSame(10, $augustPayload[22]['duration']);
    }

    public function testReplacementTextSkipsAbsentUserAsPoolmaster(): void
    {
        $pool = Pool::factory()->create(['name' => 'Oberes Gaeu']);
        $otherUser = User::factory()->create(['first_name' => 'Rainer', 'last_name' => 'Holweger']);
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'from' => '2026-08-22',
            'to' => '2026-09-12',
            'reason' => 'Urlaub',
        ]);

        Replacement::create([
            'absence_id' => $absence->id,
            'from' => '2026-08-22T00:00:00+02:00',
            'to' => '2026-09-12T23:59:59+02:00',
            'pool_id' => $pool->id,
        ]);

        Poolmaster::factory()->create([
            'pool_id' => $pool->id,
            'user_id' => $this->user->id,
            'start' => '2026-08-22',
            'end' => '2026-08-31',
        ]);

        Poolmaster::factory()->create([
            'pool_id' => $pool->id,
            'user_id' => $otherUser->id,
            'start' => '2026-08-22',
            'end' => '2026-09-12',
        ]);

        $text = $absence->fresh()->replacementText();

        $this->assertStringContainsString('Rainer Holweger', $text);
        $this->assertStringNotContainsString('Thomas Cornelius', $text);
    }

    public function testReplacementTextDoesNotLazyLoadAbsenceRelation(): void
    {
        $pool = Pool::factory()->create(['name' => 'Oberes Gaeu']);
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'from' => '2026-08-22',
            'to' => '2026-09-12',
            'reason' => 'Urlaub',
        ]);

        $replacement = Replacement::create([
            'absence_id' => $absence->id,
            'from' => '2026-08-22',
            'to' => '2026-09-12',
            'pool_id' => $pool->id,
        ]);

        $replacement = Replacement::with('pool')->findOrFail($replacement->id);
        $this->assertFalse($replacement->relationLoaded('absence'));

        $replacement->toText();

        $this->assertFalse($replacement->relationLoaded('absence'));
    }
}
