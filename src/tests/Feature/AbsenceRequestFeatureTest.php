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

use App\Http\Requests\AbsenceRequest;
use App\Models\Leave\Absence;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AbsenceRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_invalid_from_date_format_fails_validation(): void
    {
        $absence = Absence::factory()->create(['user_id' => $this->user->id]);
        $request = $this->makeRequest([
            'id' => $absence->id,
            'from' => 'not-a-date',
            'to' => '31.01.2024',
            'reason' => 'Urlaub',
        ], $absence);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('from', $validator->errors()->toArray());
    }

    public function test_iso_dates_can_be_saved(): void
    {
        $absence = Absence::factory()->create(['user_id' => $this->user->id]);
        $request = $this->makeRequest([
            'id' => $absence->id,
            'from' => '2026-05-31T22:00:00.000Z',
            'to' => '2026-06-04T21:59:59.000Z',
            'reason' => 'Urlaub',
        ], $absence);

        $validator = Validator::make($request->all(), $request->rules());
        $this->assertFalse($validator->fails());
        $request->setValidator($validator);

        $absence->update($request->validated());
        $absence->refresh();

        $this->assertSame('2026-06-01', $absence->getRawOriginal('from'));
        $this->assertSame('2026-06-04', $absence->getRawOriginal('to'));
    }

    public function test_naive_berlin_datetime_strings_stay_on_same_calendar_day(): void
    {
        $absence = Absence::factory()->create(['user_id' => $this->user->id]);
        $request = $this->makeRequest([
            'id' => $absence->id,
            'from' => '2026-08-02 00:00:00',
            'to' => '2026-08-03 23:59:59',
            'reason' => 'Urlaub',
        ], $absence);

        $validator = Validator::make($request->all(), $request->rules());
        $this->assertFalse($validator->fails());
        $request->setValidator($validator);

        $absence->update($request->validated());
        $absence->refresh();

        $this->assertSame('2026-08-02', $absence->getRawOriginal('from'));
        $this->assertSame('2026-08-03', $absence->getRawOriginal('to'));
    }

    public function test_store_rules_require_user_id(): void
    {
        $request = AbsenceRequest::create('/urlaub', 'POST', [
            'from' => '2026-05-31T22:00:00.000Z',
            'to' => '2026-06-04T21:59:59.000Z',
            'reason' => 'Urlaub',
        ]);
        $request->setContainer($this->app)->setRedirector($this->app->make('redirect'));
        $request->setUserResolver(fn () => $this->user);
        $request->setRouteResolver(fn () => new class
        {
            public function getName(): string
            {
                return 'absence.store';
            }
        });

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    public function test_iso_replacement_dates_are_normalized_to_absence_period(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'from' => '2026-06-01 00:00:00',
            'to' => '2026-06-10 23:59:59',
        ]);

        $replacementUser = User::factory()->create();
        $payload = [
            'id' => $absence->id,
            'from' => '2026-06-01T00:00:00.000Z',
            'to' => '2026-06-10T23:59:59.000Z',
            'reason' => 'Urlaub',
            'replacements' => [
                [
                    'from' => '2026-05-31T22:00:00.000Z',
                    'to' => '2026-06-04T21:59:59.000Z',
                    'users' => [$replacementUser->id],
                ],
            ],
        ];
        $request = $this->makeRequest($payload, $absence);

        $validator = Validator::make($request->all(), $request->rules());
        $this->assertFalse($validator->fails());
        $request->setValidator($validator);

        $absence->update($request->validated());
        $absence->setupReplacements($this->user, $payload['replacements']);

        $replacement = $absence->fresh()->replacements()->first();

        $this->assertNotNull($replacement);
        $this->assertSame('2026-06-01', $replacement->getRawOriginal('from'));
        $this->assertSame('2026-06-04', $replacement->getRawOriginal('to'));
    }

    public function test_naive_replacement_dates_are_normalized_to_same_berlin_calendar_days(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'from' => '2026-08-01 00:00:00',
            'to' => '2026-08-10 23:59:59',
        ]);

        $replacementUser = User::factory()->create();
        $payload = [
            'id' => $absence->id,
            'from' => '2026-08-01 00:00:00',
            'to' => '2026-08-10 23:59:59',
            'reason' => 'Urlaub',
            'replacements' => [
                [
                    'from' => '2026-08-02 00:00:00',
                    'to' => '2026-08-03 23:59:59',
                    'users' => [$replacementUser->id],
                ],
            ],
        ];
        $request = $this->makeRequest($payload, $absence);

        $validator = Validator::make($request->all(), $request->rules());
        $this->assertFalse($validator->fails());
        $request->setValidator($validator);

        $absence->update($request->validated());
        $absence->setupReplacements($this->user, $payload['replacements']);

        $replacement = $absence->fresh()->replacements()->first();

        $this->assertNotNull($replacement);
        $this->assertSame('2026-08-02', $replacement->getRawOriginal('from'));
        $this->assertSame('2026-08-03', $replacement->getRawOriginal('to'));
    }

    public function test_missing_from_field_fails_validation(): void
    {
        $absence = Absence::factory()->create(['user_id' => $this->user->id]);
        $request = $this->makeRequest([
            'id' => $absence->id,
            'to' => '31.01.2024',
            'reason' => 'Urlaub',
        ], $absence);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('from', $validator->errors()->toArray());
    }

    public function test_missing_reason_fails_validation(): void
    {
        $absence = Absence::factory()->create(['user_id' => $this->user->id]);
        $request = $this->makeRequest([
            'id' => $absence->id,
            'from' => '01.01.2024',
            'to' => '31.01.2024',
        ], $absence);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('reason', $validator->errors()->toArray());
    }

    protected function makeRequest(array $data, Absence $absence): AbsenceRequest
    {
        $request = AbsenceRequest::create('/urlaub/'.$absence->id, 'PATCH', $data);
        $request->setContainer($this->app)->setRedirector($this->app->make('redirect'));
        $request->setUserResolver(fn () => $this->user);
        $request->setRouteResolver(fn () => new class
        {
            public function getName(): string
            {
                return 'absence.update';
            }
        });
        $request->setAbsence($absence);

        return $request;
    }
}
