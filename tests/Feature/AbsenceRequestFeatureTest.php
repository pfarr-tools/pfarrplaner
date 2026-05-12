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

use App\Models\Leave\Absence;
use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsenceRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function testInvalidFromDateFormatFailsValidation(): void
    {
        $absence = Absence::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user)
            ->patch(route('absence.update', $absence->id), [
                'id' => $absence->id,
                'from' => '2024-01-01',
                'to' => '31.01.2024',
                'reason' => 'Urlaub',
            ])
            ->assertSessionHasErrors(['from']);
    }

    public function testMissingFromFieldFailsValidation(): void
    {
        $absence = Absence::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user)
            ->patch(route('absence.update', $absence->id), [
                'id' => $absence->id,
                'to' => '31.01.2024',
                'reason' => 'Urlaub',
            ])
            ->assertSessionHasErrors(['from']);
    }

    public function testMissingReasonFailsValidation(): void
    {
        $absence = Absence::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user)
            ->patch(route('absence.update', $absence->id), [
                'id' => $absence->id,
                'from' => '01.01.2024',
                'to' => '31.01.2024',
            ])
            ->assertSessionHasErrors(['reason']);
    }
}
