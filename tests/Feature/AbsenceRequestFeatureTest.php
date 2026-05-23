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

    public function testInvalidFromDateFormatFailsValidation(): void
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

    public function testIsoDatesCanBeSaved(): void
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

        $this->assertSame('2026-06-01 00:00:00', $absence->from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-04 23:59:59', $absence->to->format('Y-m-d H:i:s'));
    }

    public function testIsoReplacementDatesAreNormalizedToAbsencePeriod(): void
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
        $this->assertSame('2026-06-01 00:00:00', $replacement->from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-04 23:59:59', $replacement->to->format('Y-m-d H:i:s'));
    }

    public function testMissingFromFieldFailsValidation(): void
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

    public function testMissingReasonFailsValidation(): void
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
        $request->setRouteResolver(fn () => new class {
            public function getName(): string
            {
                return 'absence.update';
            }
        });
        $request->setAbsence($absence);

        return $request;
    }
}
